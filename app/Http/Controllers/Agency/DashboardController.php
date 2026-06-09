<?php

namespace App\Http\Controllers\Agency;

use App\Enums\IncidentStatus;
use App\Enums\SmsLogStatus;
use App\Models\Incident;
use App\Models\SmsLog;
use App\Http\Controllers\Controller;
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

        $statusCounts = Incident::where('agency_id', $agencyId)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn ($item) => [$item->status => $item->count]);

        $assignedIncidents = \App\Models\Assignment::where('agency_id', $agencyId)
            ->where('is_active', true)
            ->with('incident')
            ->count();

        $pendingResolutions = Incident::where('agency_id', $agencyId)
            ->whereIn('status', [
                IncidentStatus::InProgress->value,
                IncidentStatus::PendingInfo->value,
            ])
            ->count();

        $recentUpdates = \App\Models\StatusUpdate::whereHas('incident', function ($query) use ($agencyId) {
            $query->where('agency_id', $agencyId);
        })
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['incident_id', 'from_status', 'to_status', 'comment', 'created_at']);

        $smsReceived = SmsLog::whereHas('incident', function ($query) use ($agencyId) {
            $query->where('agency_id', $agencyId);
        })
            ->where('created_at', '>=', now()->startOfWeek())
            ->count();

        return response()->json([
            'agency_id' => $agencyId,
            'incident_status_breakdown' => $statusCounts,
            'total_assigned_incidents' => $assignedIncidents,
            'pending_resolutions' => $pendingResolutions,
            'recent_status_updates' => $recentUpdates,
            'sms_alerts_this_week' => $smsReceived,
        ]);
    }
}


