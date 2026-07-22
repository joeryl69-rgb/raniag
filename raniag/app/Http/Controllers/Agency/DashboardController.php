<?php

namespace App\Http\Controllers\Agency;

use App\Enums\IncidentStatus;
use App\Enums\SmsLogStatus;
use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Incident;
use App\Models\SmsLog;
use App\Models\StatusUpdate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        return view('agency.dashboard');
    }

    public function api(Request $request): JsonResponse
    {
        $agencyId = $request->user()?->agency_id;
        abort_if(! $agencyId, 403, 'No agency is associated with this account.');

        // Stable KPI mapping expected by the agency dashboard
        // UI uses: assigned | in_progress | pending_info
        $statusCounts = [
            IncidentStatus::Assigned->value => 0,
            IncidentStatus::InProgress->value => 0,
            IncidentStatus::PendingInfo->value => 0,
        ];

        $assignedIncidents = Assignment::query()
            ->join('incidents', 'incidents.id', '=', 'assignments.incident_id')
            ->where('assignments.agency_id', $agencyId)
            ->where('assignments.is_active', true)
            ->whereColumn('assignments.created_at', '>=', 'incidents.created_at')
            ->count();

        // KPI counts derived from active assignments + incident workflow status
        $statusRows = Assignment::query()
            ->where('assignments.agency_id', $agencyId)
            ->where('assignments.is_active', true)
            ->join('incidents', 'incidents.id', '=', 'assignments.incident_id')
            ->whereColumn('assignments.created_at', '>=', 'incidents.created_at')
            ->selectRaw('incidents.status, COUNT(*) as count')
            ->groupBy('incidents.status')
            ->get();

        foreach ($statusRows as $row) {
            $val = $row->status instanceof \BackedEnum ? $row->status->value : (string) $row->status;
            $statusCounts[$val] = (int) $row->count;
        }

        $pendingResolutions = Assignment::query()
            ->where('assignments.agency_id', $agencyId)
            ->where('assignments.is_active', true)
            ->join('incidents', 'incidents.id', '=', 'assignments.incident_id')
            ->whereColumn('assignments.created_at', '>=', 'incidents.created_at')
            ->whereIn('incidents.status', [
                IncidentStatus::InProgress->value,
                IncidentStatus::PendingInfo->value,
            ])
            ->count();

        // Recent updates shown in feed + map polling
        // Ensure:
        // - only public updates
        // - only for incidents with an active assignment to this agency
        $recentUpdates = StatusUpdate::query()
            ->where('is_public', true)
            ->whereHas('incident', function ($query) use ($agencyId) {
                $query->whereHas('assignments', function ($a) use ($agencyId) {
                    $a->where('assignments.agency_id', $agencyId)
                        ->where('assignments.is_active', true)
                        ->whereColumn('assignments.created_at', '>=', 'incidents.created_at');
                });
            })
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['incident_id', 'from_status', 'to_status', 'comment', 'created_at']);

        // SMS alerts received this week for incidents assigned to this agency (active)
        $smsReceived = SmsLog::query()
            ->where('created_at', '>=', now()->startOfWeek())
            ->whereIn('status', [
                SmsLogStatus::Sent->value,
                SmsLogStatus::Pending->value,
            ])
            ->whereHas('incident', function ($query) use ($agencyId) {
                $query->whereHas('assignments', function ($a) use ($agencyId) {
                    $a->where('assignments.agency_id', $agencyId)
                        ->where('assignments.is_active', true)
                        ->whereColumn('assignments.created_at', '>=', 'incidents.created_at');
                });
            })
            ->count();

        // Active dispatches table data for the UI map/table
        // Return priority/status as strings matching UI comparisons.
        $activeDispatches = Incident::query()
            ->whereHas('assignments', function ($q) use ($agencyId) {
                $q->where('assignments.agency_id', $agencyId)
                    ->where('assignments.is_active', true)
                    ->whereColumn('assignments.created_at', '>=', 'incidents.created_at');
            })
            ->whereIn('status', [
                IncidentStatus::Assigned->value,
                IncidentStatus::InProgress->value,
                IncidentStatus::PendingInfo->value,
            ])
            ->with('incidentType')
            ->orderByDesc('reported_at')
            ->get()
            ->map(function (Incident $inc) {
                $priority = $inc->priority;
                if ($priority instanceof \BackedEnum) {
                    $priority = $priority->value;
                }

                // Ensure string for UI: low|medium|high|critical
                $priority = is_string($priority) ? $priority : (string) $priority;

                // Ensure string for UI: assigned|in_progress|pending_info
                $status = $inc->status instanceof \BackedEnum ? $inc->status->value : (string) $inc->status;

                return [
                    'id' => $inc->id,
                    'tracking_number' => $inc->tracking_number,
                    'incident_type' => $inc->incidentType,
                    'priority' => $priority,
                    'status' => $status,
                    'barangay' => $inc->barangay,
                    'latitude' => $inc->latitude,
                    'longitude' => $inc->longitude,
                    'reported_at' => $inc->reported_at?->toDateTimeString(),
                ];
            });

        return response()->json([
            'agency_id' => $agencyId,
            'incident_status_breakdown' => $statusCounts,
            'total_assigned_incidents' => $assignedIncidents,
            'pending_resolutions' => $pendingResolutions,
            'recent_status_updates' => $recentUpdates ?? $recentUpdates,
            'sms_alerts_this_week' => $smsReceived,
            'active_dispatches' => $activeDispatches,
        ]);
    }
}
