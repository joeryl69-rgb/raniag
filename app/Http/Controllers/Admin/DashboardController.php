<?php

namespace App\Http\Controllers\Admin;

use App\Enums\IncidentStatus;
use App\Enums\SmsLogStatus;
use App\Models\Agency;
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
        return view(‘admin.dashboard’);
    }

    public function api(Request $request): JsonResponse
    {
        $statusCounts = Incident::selectRaw(‘status, COUNT(*) as count’)
            ->groupBy(‘status’)
            ->get()
            ->mapWithKeys(fn ($item) => [$item->status => $item->count]);

        $recentIncidents = Incident::with([‘incidentType’, ‘agency’])
            ->orderByDesc(‘reported_at’)
            ->limit(10)
            ->get();

        $activeAssignments = \App\Models\Assignment::where(‘is_active’, true)
            ->with([‘incident’, ‘agency’])
            ->count();

        $completedThisWeek = \App\Models\Assignment::where(‘is_active’, false)
            ->where(‘completed_at’, ‘>=’, now()->startOfWeek())
            ->count();

        $smsStats = [
            ‘sent’ => SmsLog::where(‘status’, SmsLogStatus::Sent->value)->count(),
            ‘failed’ => SmsLog::where(‘status’, SmsLogStatus::Failed->value)->count(),
            ‘pending’ => SmsLog::where(‘status’, SmsLogStatus::Pending->value)->count(),
        ];

        $agencies = Agency::where(‘is_active’, true)->count();

        return response()->json([
            ‘incident_status_breakdown’ => $statusCounts,
            ‘total_incidents’ => Incident::count(),
            ‘active_agencies’ => $agencies,
            ‘recent_incidents’ => $recentIncidents,
            ‘active_assignments’ => $activeAssignments,
            ‘assignments_completed_this_week’ => $completedThisWeek,
            ‘sms_stats’ => $smsStats,
        ]);
    }
}


