<?php

namespace App\Http\Controllers\Agency;

use App\Enums\IncidentStatus;
use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Services\ActivityLogService;
use App\Services\IncidentArchiveZipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ArchivedReportController extends Controller
{
    private const VALID_STATUSES = [IncidentStatus::Resolved->value, IncidentStatus::Closed->value];

    public function __construct(
        private readonly IncidentArchiveZipService $zipService,
        private readonly ActivityLogService $activityLog,
    ) {}

    public function index(Request $request): View
    {
        $agencyId = $request->user()?->agency_id;
        abort_if(! $agencyId, 403, 'No agency is associated with this account.');

        $query = Incident::query()
            ->whereIn('status', self::VALID_STATUSES)
            ->whereHas('currentAssignments', fn ($q) => $q->where('agency_id', $agencyId))
            ->with(['incidentType'])
            ->withCount(['incidentDocuments', 'evidence']);

        $q = trim((string) $request->string('q'));
        if ($q !== '') {
            $query->where('tracking_number', 'like', "%{$q}%");
        }

        \App\Support\Filters::dateRange($query, 'reported_at', [
            'date_from' => $request->string('date_from')->trim()->value(),
            'date_to' => $request->string('date_to')->trim()->value(),
        ]);

        $barangay = $request->string('barangay')->value();
        if ($barangay !== '' && $barangay !== 'all') {
            $query->where('barangay', $barangay);
        }

        $status = $request->string('status')->value();
        if ($status !== '' && $status !== 'all' && in_array($status, self::VALID_STATUSES, true)) {
            $query->where('status', $status);
        }

        $sort = $request->string('sort')->value() ?: 'reported_at';
        $direction = $request->string('direction')->value() === 'asc' ? 'asc' : 'desc';
        $sortable = ['reported_at', 'tracking_number', 'barangay', 'status'];
        $query->orderBy(in_array($sort, $sortable, true) ? $sort : 'reported_at', $direction);

        $reports = $query->paginate(12)->withQueryString();

        $barangays = Incident::query()
            ->whereIn('status', self::VALID_STATUSES)
            ->whereHas('currentAssignments', fn ($q) => $q->where('agency_id', $agencyId))
            ->whereNotNull('barangay')
            ->distinct()
            ->orderBy('barangay')
            ->pluck('barangay');

        return view('agency.archived_reports.index', compact('reports', 'barangays'));
    }

    public function show(Request $request, Incident $incident): View
    {
        $this->authorizeAccess($request, $incident);

        $incident->load(['incidentType', 'incidentDocuments', 'evidence', 'statusUpdates']);

        return view('agency.archived_reports.show', compact('incident'));
    }

    /**
     * Step 1 of the download flow: verify the account password before
     * allowing any archive transfer, as required by the security policy.
     */
    public function verifyPassword(Request $request, Incident $incident): JsonResponse
    {
        $this->authorizeAccess($request, $incident);

        $data = $request->validate(['password' => ['required', 'string']]);

        if (! Hash::check($data['password'], $request->user()->password)) {
            return response()->json(['message' => 'Incorrect password. Please try again.'], 422);
        }

        // Short-lived signed token authorizes the immediate follow-up download call.
        $token = encrypt([
            'incident_id' => $incident->id,
            'user_id' => $request->user()->id,
            'expires_at' => now()->addMinutes(2)->timestamp,
        ]);

        return response()->json(['message' => 'Password verified.', 'download_token' => $token]);
    }

    public function download(Request $request, Incident $incident)
    {
        $this->authorizeAccess($request, $incident);

        $request->validate(['download_token' => ['required', 'string']]);

        try {
            $payload = decrypt($request->input('download_token'));
        } catch (\Throwable) {
            abort(422, 'Invalid or expired download token. Please re-enter your password.');
        }

        abort_if(
            ($payload['incident_id'] ?? null) !== $incident->id
            || ($payload['user_id'] ?? null) !== $request->user()->id
            || now()->timestamp > ($payload['expires_at'] ?? 0),
            422,
            'Invalid or expired download token. Please re-enter your password.'
        );

        $zipPath = $this->zipService->build($incident);

        $this->activityLog->log(
            description: "Downloaded archived report ZIP for incident {$incident->tracking_number}",
            user: $request->user(),
            subject: $incident,
            event: 'archived_report.downloaded',
            logName: 'agency',
            request: $request,
        );

        $filename = 'raniag-archive-'.$incident->tracking_number.'.zip';

        return response()->download($zipPath, $filename)->deleteFileAfterSend(true);
    }

    /**
     * Step 1 of the bulk download flow (select-all/multi-select on the
     * repository index): verify password, then hand back a signed token
     * scoped to the exact set of incident IDs the agency selected.
     */
    public function verifyPasswordBulk(Request $request): JsonResponse
    {
        $agencyId = $request->user()?->agency_id;
        abort_if(! $agencyId, 403, 'No agency is associated with this account.');

        $data = $request->validate([
            'password' => ['required', 'string'],
            'incident_ids' => ['required', 'array', 'min:1'],
            'incident_ids.*' => ['integer'],
        ]);

        if (! Hash::check($data['password'], $request->user()->password)) {
            return response()->json(['message' => 'Incorrect password. Please try again.'], 422);
        }

        $incidentIds = Incident::query()
            ->whereIn('id', $data['incident_ids'])
            ->whereIn('status', self::VALID_STATUSES)
            ->whereHas('currentAssignments', fn ($q) => $q->where('agency_id', $agencyId))
            ->pluck('id');

        abort_if($incidentIds->isEmpty(), 422, 'None of the selected reports are available for download.');

        $token = encrypt([
            'incident_ids' => $incidentIds->all(),
            'user_id' => $request->user()->id,
            'expires_at' => now()->addMinutes(2)->timestamp,
        ]);

        return response()->json(['message' => 'Password verified.', 'download_token' => $token]);
    }

    public function downloadBulk(Request $request)
    {
        $agencyId = $request->user()?->agency_id;
        abort_if(! $agencyId, 403, 'No agency is associated with this account.');

        $request->validate(['download_token' => ['required', 'string']]);

        try {
            $payload = decrypt($request->input('download_token'));
        } catch (\Throwable) {
            abort(422, 'Invalid or expired download token. Please re-enter your password.');
        }

        abort_if(
            ($payload['user_id'] ?? null) !== $request->user()->id
            || now()->timestamp > ($payload['expires_at'] ?? 0),
            422,
            'Invalid or expired download token. Please re-enter your password.'
        );

        $incidents = Incident::query()
            ->whereIn('id', $payload['incident_ids'] ?? [])
            ->whereIn('status', self::VALID_STATUSES)
            ->whereHas('currentAssignments', fn ($q) => $q->where('agency_id', $agencyId))
            ->get();

        abort_if($incidents->isEmpty(), 404, 'No matching reports found.');

        $zipPath = $this->zipService->buildMany($incidents);

        $this->activityLog->log(
            description: 'Downloaded bulk archived report ZIP ('.$incidents->count().' incidents)',
            user: $request->user(),
            event: 'archived_report.bulk_downloaded',
            logName: 'agency',
            request: $request,
        );

        $filename = 'raniag-archive-bulk-'.now()->format('Ymd-His').'.zip';

        return response()->download($zipPath, $filename)->deleteFileAfterSend(true);
    }

    private function authorizeAccess(Request $request, Incident $incident): void
    {
        $agencyId = $request->user()?->agency_id;
        abort_if(! $agencyId, 403, 'No agency is associated with this account.');

        abort_unless(in_array(
            is_object($incident->status) ? $incident->status->value : $incident->status,
            self::VALID_STATUSES,
            true
        ), 404);

        $hasAssignment = $incident->currentAssignments()
            ->where('agency_id', $agencyId)
            ->exists();

        abort_if(! $hasAssignment, 403, 'This report is not associated with your agency.');
    }
}
