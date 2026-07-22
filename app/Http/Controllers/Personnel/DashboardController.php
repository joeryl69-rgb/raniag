<?php

namespace App\Http\Controllers\Personnel;

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
        return view('personnel.dashboard');
    }

    public function api(Request $request): JsonResponse
    {
        $personnelId = $request->user()?->id;
        abort_if(! $personnelId, 403, 'No personnel account is associated with this login.');

        $statusCounts = [
            IncidentStatus::Assigned->value => 0,
            IncidentStatus::InProgress->value => 0,
            IncidentStatus::PendingInfo->value => 0,
        ];

        $assignedIncidents = Assignment::query()
            ->join('incidents', 'incidents.id', '=', 'assignments.incident_id')
            ->where('assignments.assigned_to', $personnelId)
            ->where('assignments.is_active', true)
            ->whereColumn('assignments.created_at', '>=', 'incidents.created_at')
            ->count();

        $statusRows = Assignment::query()
            ->where('assignments.assigned_to', $personnelId)
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
            ->where('assignments.assigned_to', $personnelId)
            ->where('assignments.is_active', true)
            ->join('incidents', 'incidents.id', '=', 'assignments.incident_id')
            ->whereColumn('assignments.created_at', '>=', 'incidents.created_at')
            ->whereIn('incidents.status', [
                IncidentStatus::InProgress->value,
                IncidentStatus::PendingInfo->value,
            ])
            ->count();

        $recentUpdates = StatusUpdate::query()
            ->where('is_public', true)
            ->whereHas('incident', function ($query) use ($personnelId) {
                $query->whereHas('assignments', function ($a) use ($personnelId) {
                    $a->where('assigned_to', $personnelId)
                        ->where('is_active', true)
                        ->whereColumn('assignments.created_at', '>=', 'incidents.created_at');
                });
            })
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['incident_id', 'from_status', 'to_status', 'comment', 'created_at']);

        $smsReceived = SmsLog::query()
            ->where('created_at', '>=', now()->startOfWeek())
            ->whereIn('status', [
                SmsLogStatus::Sent->value,
                SmsLogStatus::Pending->value,
            ])
            ->whereHas('incident', function ($query) use ($personnelId) {
                $query->whereHas('assignments', function ($a) use ($personnelId) {
                    $a->where('assigned_to', $personnelId)
                        ->where('is_active', true)
                        ->whereColumn('assignments.created_at', '>=', 'incidents.created_at');
                });
            })
            ->count();

        $activeDispatches = Incident::query()
            ->whereHas('assignments', function ($q) use ($personnelId) {
                $q->where('assigned_to', $personnelId)
                    ->where('is_active', true)
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

                $priority = is_string($priority) ? $priority : (string) $priority;
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
            'personnel_id' => $personnelId,
            'incident_status_breakdown' => $statusCounts,
            'total_assigned_incidents' => $assignedIncidents,
            'pending_resolutions' => $pendingResolutions,
            'recent_status_updates' => $recentUpdates ?? $recentUpdates,
            'sms_alerts_this_week' => $smsReceived,
            'active_dispatches' => $activeDispatches,
        ]);
    }
}
