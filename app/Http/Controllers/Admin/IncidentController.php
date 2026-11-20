<?php

namespace App\Http\Controllers\Admin;

use App\Enums\IncidentPriority;
use App\Enums\IncidentStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ValidateIncidentRequest;
use App\Models\Agency;
use App\Models\Incident;
use App\Models\IncidentType;
use App\Models\MunicipalityContact;
use App\Models\User;
use App\Repositories\Contracts\IncidentRepositoryInterface;
use App\Services\AssignmentService;
use App\Services\IncidentService;
use App\Services\NotificationService;
use App\Support\FilterOptions;
use App\Support\PlaceScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class IncidentController extends Controller
{
    public function __construct(
        private readonly IncidentRepositoryInterface $incidents,
        private readonly IncidentService $incidentService,
        private readonly AssignmentService $assignmentService,
        private readonly NotificationService $notifications,
    ) {}

    public function index(Request $request): View|JsonResponse
    {
        $perPage = min(100, max(5, (int) $request->integer('per_page', 10)));

        $filters = [
            'q' => $request->string('q')->trim()->value(),
            'status' => $request->string('status')->value() ?: IncidentStatus::Submitted->value,
            'incident_type_id' => $request->integer('incident_type_id') ?: null,
            'priority' => $request->string('priority')->value(),
            'jurisdiction' => $request->string('jurisdiction')->value(),
            'date_from' => $request->string('date_from')->trim()->value(),
            'date_to' => $request->string('date_to')->trim()->value(),
            'sort' => $request->string('sort')->value(),
            'direction' => $request->string('direction')->value(),
        ];

        $incidents = $this->incidents->paginateAll(array_filter($filters), $perPage);

        // Preserve query string for pagination links
        $incidents->appends($request->except('page'));

        if ($request->wantsJson()) {
            return response()->json($incidents);
        }

        $usedTypeIds = Incident::query()->whereNotNull('incident_type_id')->distinct()->pluck('incident_type_id');
        $incidentTypes = IncidentType::query()
            ->where(function ($query) use ($usedTypeIds, $request) {
                $query->whereIn('id', $usedTypeIds);
                if ($request->filled('incident_type_id')) {
                    $query->orWhere('id', $request->integer('incident_type_id'));
                }
            })
            ->orderBy('name')
            ->get();

        $statusCounts = Incident::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');
        $countStatus = function (IncidentStatus $status) use ($statusCounts): int {
            return (int) ($statusCounts[$status->value] ?? 0);
        };
        $incidentKpis = [
            'total' => (int) $statusCounts->sum(),
            'submitted' => $countStatus(IncidentStatus::Submitted),
            'open' => $countStatus(IncidentStatus::Submitted)
                + $countStatus(IncidentStatus::Received)
                + $countStatus(IncidentStatus::Assigned)
                + $countStatus(IncidentStatus::InProgress)
                + $countStatus(IncidentStatus::PendingInfo),
            'in_progress' => $countStatus(IncidentStatus::InProgress),
            'resolved' => $countStatus(IncidentStatus::Resolved),
        ];
        $outsideIncidents = Incident::query();
        PlaceScope::applyBarangayFilter($outsideIncidents, 'outside');
        $incidentKpis['outside'] = $outsideIncidents->count();
        $queueStatuses = FilterOptions::enums(IncidentStatus::cases(), $statusCounts->keys());
        $queuePriorities = FilterOptions::enums(
            IncidentPriority::cases(),
            Incident::query()->distinct()->pluck('priority'),
            $request->input('priority')
        );

        return view('admin.incidents.index', compact('incidents', 'incidentTypes', 'incidentKpis', 'queueStatuses', 'queuePriorities'));
    }

    public function show(Request $request, int $incident): View|JsonResponse
    {
        $record = $this->incidents->findById($incident);

        abort_if(! $record, 404);

        if ($request->wantsJson()) {
            return response()->json($record);
        }

        // Agencies/personnel already assigned (at any point) to this incident are excluded
        // from every dispatch/re-dispatch checklist below — once dispatched, an agency or
        // personnel cannot be dispatched to the same incident again.
        $alreadyAssignedAgencyIds = $record->assignments()->whereNotNull('agency_id')->pluck('agency_id')->unique();
        $alreadyAssignedPersonnelIds = $record->assignments()->whereNotNull('assigned_to')->pluck('assigned_to')->unique();

        $agencies = Agency::where('is_active', true)
            ->whereNotIn('id', $alreadyAssignedAgencyIds)
            ->orderBy('name')
            ->get();
        $personnel = User::where('role', UserRole::Personnel)
            ->where('is_active', true)
            ->whereNotIn('id', $alreadyAssignedPersonnelIds)
            ->orderBy('name')
            ->get();
        $referralContacts = MunicipalityContact::query()
            ->where('is_active', true)
            ->orderBy('municipality')
            ->orderBy('office_name')
            ->get();

        return view('admin.incidents.show', [
            // 'evidence' intentionally omitted here — repository supplies a filtered evidence relation to avoid showing orphaned files
            'incident' => $record,
            'agencies' => $agencies,
            'personnel' => $personnel,
            'referralContacts' => $referralContacts,
        ]);
    }

    public function validate(ValidateIncidentRequest $request, int $incident): RedirectResponse|JsonResponse
    {
        $record = $this->incidents->findById($incident);
        abort_if(! $record, 404);

        $data = $request->validated();

        if ($data['action'] === 'reject') {
            $this->incidentService->recordStatusChange(
                incident: $record,
                toStatus: IncidentStatus::Rejected,
                user: $request->user(),
                comment: $data['notes'] ?? 'Report rejected',
                isPublic: true,
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Incident report rejected.',
                    'incident' => $record->fresh(),
                ]);
            }

            return redirect()
                ->route('admin.incidents.show', $record->id)
                ->with('success', 'Incident report has been rejected.');
        }

        if ($data['action'] === 'outside_aor') {
            $contact = MunicipalityContact::query()
                ->where('is_active', true)
                ->findOrFail($data['municipality_contact_id']);

            DB::transaction(function () use ($record, $contact, $data, $request) {
                $this->incidentService->recordStatusChange(
                    incident: $record,
                    toStatus: IncidentStatus::OutsideAor,
                    user: $request->user(),
                    comment: 'Report referred to '.$contact->office_name.' in '.$contact->municipality.'.',
                    isPublic: true,
                );

                $record->referral()->create([
                    'municipality_contact_id' => $contact->id,
                    'referred_by' => $request->user()->id,
                    'municipality' => $contact->municipality,
                    'office_name' => $contact->office_name,
                    'hotline' => $contact->hotline,
                    'location_address' => $contact->address,
                    'latitude' => $contact->latitude,
                    'longitude' => $contact->longitude,
                    'public_note' => $data['notes'],
                ]);
            });

            $this->notifications->notifyOutsideAor($record, $request->user());

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Incident marked as outside AOR and referred.',
                    'incident' => $record->fresh()->load('referral'),
                ]);
            }

            return redirect()
                ->route('admin.incidents.show', $record->id)
                ->with('success', 'Incident referred to '.$contact->office_name.' in '.$contact->municipality.'. The reporter can see the coordination details on tracking.');
        }

        if ($record->status === IncidentStatus::Submitted) {
            $this->incidentService->recordStatusChange(
                incident: $record,
                toStatus: IncidentStatus::Received,
                user: $request->user(),
                comment: 'Report validated and received',
                isPublic: true,
            );
        }

        $agencyIds = $data['assigned_agency_id'] ?? [];
        $personnelIds = $data['assigned_personnel_id'] ?? [];

        $agencies = Agency::whereIn('id', $agencyIds)->where('is_active', true)->get();
        $personnel = User::whereIn('id', $personnelIds)
            ->where('role', UserRole::Personnel)
            ->where('is_active', true)
            ->get();

        if ($agencies->isEmpty() && $personnel->isEmpty()) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'No valid agencies or personnel found for assignment.'], 422);
            }

            return redirect()->back()->withInput()->withErrors(['assigned_agency_id' => 'Please select at least one agency or personnel to assign.']);
        }

        $assignments = [];
        foreach ($agencies as $agency) {
            $assignment = $this->assignmentService->assignToAgency(
                incident: $record,
                agency: $agency,
                assignedBy: $request->user(),
                data: ['notes' => $data['notes'] ?? null],
            );
            $assignments[] = $assignment;
        }

        foreach ($personnel as $person) {
            $assignment = $this->assignmentService->assignToPersonnel(
                incident: $record,
                personnel: $person,
                assignedBy: $request->user(),
                data: ['notes' => $data['notes'] ?? null],
            );
            $assignments[] = $assignment;
        }

        $assignmentNames = collect()
            ->concat($agencies->pluck('code'))
            ->concat($personnel->map(fn ($person) => $person->display_title))
            ->join(', ');

        // Ensure final incident status is Assigned after creating assignments
        try {
            $latestIncident = $record->fresh();
            if ($latestIncident->status !== IncidentStatus::Assigned) {
                $this->incidentService->recordStatusChange(
                    incident: $latestIncident,
                    toStatus: IncidentStatus::Assigned,
                    user: $request->user(),
                    comment: 'Assigned to: '.$assignmentNames,
                    isPublic: true,
                );
            }
        } catch (\Exception $e) {
            // Log but proceed — assignments were created successfully
            Log::warning('Failed to finalize incident status to Assigned: '.$e->getMessage());
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Incident approved and assigned.',
                'incident' => $record->fresh(),
                'assignments' => $assignments,
            ], 201);
        }

        return redirect()
            ->route('admin.incidents.show', $record->id)
            ->with('success', 'Incident approved and successfully assigned to: '.$assignmentNames.'.');
    }

    public function reply(Request $request, int $incident): RedirectResponse|JsonResponse
    {
        $record = $this->incidents->findById($incident);
        abort_if(! $record, 404);

        abort_unless(
            $record->status === IncidentStatus::PendingInfo,
            422,
            'This incident is not currently awaiting information from an agency.'
        );

        $data = $request->validate([
            'reply' => ['required', 'string', 'max:2000'],
            // "confirmation dropdown": lets the admin either just answer the
            // agency's question (stay in Pending Information) or answer AND
            // immediately clear the hold so the agency resumes investigating,
            // instead of the agency having to make a separate follow-up move.
            'resume_investigation' => ['nullable', 'boolean'],
        ]);

        $toStatus = $request->boolean('resume_investigation')
            ? IncidentStatus::InProgress
            : IncidentStatus::PendingInfo;

        $this->incidentService->recordStatusChange(
            incident: $record,
            toStatus: $toStatus,
            user: $request->user(),
            comment: 'Admin reply: '.$data['reply'],
            isPublic: false,
        );

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Reply sent to the assigned agency.',
                'incident' => $record->fresh(),
            ]);
        }

        return redirect()
            ->route('admin.incidents.show', $record->id)
            ->with('success', $toStatus === IncidentStatus::InProgress
                ? 'Reply sent and investigation resumed.'
                : 'Reply sent to the assigned agency.');
    }

    public function assignments(int $incident, Request $request): JsonResponse
    {
        $record = $this->incidents->findById($incident);
        abort_if(! $record, 404);

        $assignments = $record->assignments()
            ->with(['agency', 'assigner', 'assignee'])
            ->paginate((int) $request->integer('per_page', 15));

        return response()->json($assignments);
    }
}
