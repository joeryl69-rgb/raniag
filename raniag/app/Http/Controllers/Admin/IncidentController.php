<?php

namespace App\Http\Controllers\Admin;

use App\Enums\IncidentStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ValidateIncidentRequest;
use App\Models\Agency;
use App\Models\IncidentType;
use App\Models\User;
use App\Repositories\Contracts\IncidentRepositoryInterface;
use App\Services\AssignmentService;
use App\Services\IncidentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class IncidentController extends Controller
{
    public function __construct(
        private readonly IncidentRepositoryInterface $incidents,
        private readonly IncidentService $incidentService,
        private readonly AssignmentService $assignmentService,
    ) {}

    public function index(Request $request): View|JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 15);

        $filters = [
            'q' => $request->string('q')->trim()->value(),
            'status' => $request->string('status')->value(),
            'incident_type_id' => $request->integer('incident_type_id') ?: null,
        ];

        $incidents = $this->incidents->paginateAll(array_filter($filters), $perPage);

        // Preserve query string for pagination links
        $incidents->appends($request->except('page'));

        if ($request->wantsJson()) {
            return response()->json($incidents);
        }

        // Provide incident types for filter dropdown
        $incidentTypes = IncidentType::orderBy('name')->get();

        return view('admin.incidents.index', compact('incidents', 'incidentTypes'));
    }

    public function show(Request $request, int $incident): View|JsonResponse
    {
        $record = $this->incidents->findById($incident);

        abort_if(! $record, 404);

        if ($request->wantsJson()) {
            return response()->json($record);
        }

        $agencies = Agency::where('is_active', true)->orderBy('name')->get();
        $personnel = User::where('role', UserRole::Personnel)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.incidents.show', [
            // 'evidence' intentionally omitted here — repository supplies a filtered evidence relation to avoid showing orphaned files
            'incident' => $record,
            'agencies' => $agencies,
            'personnel' => $personnel,
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
