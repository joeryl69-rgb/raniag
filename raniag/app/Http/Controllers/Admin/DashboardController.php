<?php

namespace App\Http\Controllers\Admin;

use App\Enums\IncidentStatus;
use App\Enums\SmsLogStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Agency;
use App\Models\Assignment;
use App\Models\Incident;
use App\Models\SmsLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.dashboard');
    }

    public function smsLogs()
    {
        $logs = SmsLog::with('incident')->latest()->paginate(15);

        return view('admin.sms-logs.index', compact('logs'));
    }

    public function auditLogs()
    {
        $logs = ActivityLog::with('user')->latest()->paginate(15);

        return view('admin.audit-logs.index', compact('logs'));
    }

    public function api(Request $request): JsonResponse
    {
        $statusCountsRaw = Incident::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn ($item) => [$item->status instanceof \BackedEnum ? $item->status->value : (string) $item->status => (int) $item->count]);

        // Normalize keys to match the UI
        // submitted, in_progress, resolved, closed
        $statusCounts = [
            'submitted' => $statusCountsRaw[IncidentStatus::Submitted->value] ?? 0,
            'in_progress' => $statusCountsRaw[IncidentStatus::InProgress->value] ?? 0,
            'resolved' => $statusCountsRaw[IncidentStatus::Resolved->value] ?? 0,
            'closed' => $statusCountsRaw[IncidentStatus::Closed->value] ?? 0,
        ];

        $recentIncidents = Incident::with(['incidentType', 'agency'])
            ->orderByDesc('reported_at')
            ->limit(10)
            ->get()
            ->map(function (Incident $inc) {
                return [
                    'id' => $inc->id,
                    'tracking_number' => $inc->tracking_number,
                    'incidentType' => $inc->incidentType,
                    'incident_type' => $inc->incidentType,
                    'priority' => $inc->priority instanceof \BackedEnum ? $inc->priority->value : $inc->priority,
                    'status' => $inc->status instanceof \BackedEnum ? $inc->status->value : (string) $inc->status,
                    'reported_at' => $inc->reported_at?->toDateTimeString(),
                    'latitude' => $inc->latitude,
                    'longitude' => $inc->longitude,
                    'agency' => $inc->agency,
                ];
            });

        $activeAssignments = Assignment::query()
            ->join('incidents', 'incidents.id', '=', 'assignments.incident_id')
            ->where('assignments.is_active', true)
            ->whereColumn('assignments.created_at', '>=', 'incidents.created_at')
            ->count();

        $completedThisWeek = Assignment::query()
            ->join('incidents', 'incidents.id', '=', 'assignments.incident_id')
            ->where('assignments.is_active', false)
            ->where('assignments.completed_at', '>=', now()->startOfWeek())
            ->whereColumn('assignments.created_at', '>=', 'incidents.created_at')
            ->count();

        $smsStats = [
            'sent' => SmsLog::where('status', SmsLogStatus::Sent->value)->count(),
            'failed' => SmsLog::where('status', SmsLogStatus::Failed->value)->count(),
            'pending' => SmsLog::where('status', SmsLogStatus::Pending->value)->count(),
        ];

        $agencies = Agency::where('is_active', true)->count();

        // 1. Category Distribution
        $categoryCounts = Incident::selectRaw('incident_types.name, COUNT(*) as count')
            ->join('incident_types', 'incidents.incident_type_id', '=', 'incident_types.id')
            ->groupBy('incident_types.name')
            ->get()
            ->mapWithKeys(fn ($item) => [$item->name => (int) $item->count]);

        // 2. Barangay Distribution
        $barangayCounts = Incident::selectRaw('barangay, COUNT(*) as count')
            ->whereNotNull('barangay')
            ->groupBy('barangay')
            ->orderByDesc('count')
            ->limit(8)
            ->get()
            ->mapWithKeys(fn ($item) => [$item->barangay => (int) $item->count]);

        // 3. 6-Week Volume Trends
        $weeklyTrends = [];
        for ($i = 5; $i >= 0; $i--) {
            $start = now()->subWeeks($i)->startOfWeek();
            $end = now()->subWeeks($i)->endOfWeek();
            $count = Incident::whereBetween('reported_at', [$start, $end])->count();
            $weeklyTrends[] = [
                'label' => 'Wk '.now()->subWeeks($i)->format('W'),
                'count' => $count,
            ];
        }

        // 4. Average Resolution Hours
        $avgResolutionHours = (int) Assignment::query()
            ->join('incidents', 'incidents.id', '=', 'assignments.incident_id')
            ->where('assignments.is_active', false)
            ->whereNotNull('assignments.completed_at')
            ->whereColumn('assignments.created_at', '>=', 'incidents.created_at')
            ->selectRaw('ROUND(AVG(TIMESTAMPDIFF(HOUR, assignments.assigned_at, assignments.completed_at))) as avg_hours')
            ->value('avg_hours') ?? 0;

        // 5. Agency Response Times
        $agencyResponseTimes = Assignment::query()
            ->join('incidents', 'incidents.id', '=', 'assignments.incident_id')
            ->where('assignments.is_active', false)
            ->whereNotNull('assignments.completed_at')
            ->whereColumn('assignments.created_at', '>=', 'incidents.created_at')
            ->join('agencies', 'agencies.id', '=', 'assignments.agency_id')
            ->selectRaw('agencies.code as agency_code, ROUND(AVG(TIMESTAMPDIFF(HOUR, assignments.assigned_at, assignments.completed_at)), 1) as avg_hours')
            ->groupBy('agencies.id', 'agencies.code')
            ->get()
            ->mapWithKeys(fn ($item) => [$item->agency_code => (float) $item->avg_hours]);

        // 6. Seasonal Trends (Wet vs Dry Season)
        // Philippines: Dry (Dec-May), Wet (Jun-Nov)
        $seasonalCounts = [
            'Dry Season' => Incident::whereRaw('MONTH(reported_at) IN (12, 1, 2, 3, 4, 5)')->count(),
            'Wet Season' => Incident::whereRaw('MONTH(reported_at) IN (6, 7, 8, 9, 10, 11)')->count(),
        ];

        // 7. Redundancy Tracker (Hotspots by Barangay and Type)
        $redundancyData = Incident::selectRaw('barangay, incident_types.name as incident_type, COUNT(*) as count')
            ->join('incident_types', 'incidents.incident_type_id', '=', 'incident_types.id')
            ->whereNotNull('barangay')
            ->groupBy('barangay', 'incident_types.name')
            ->having('count', '>', 1)
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'barangay' => $item->barangay,
                    'type' => $item->incident_type,
                    'count' => $item->count,
                ];
            });

        return response()->json([
            'incident_status_breakdown' => $statusCounts,
            'total_incidents' => Incident::count(),
            'active_agencies' => $agencies,
            'recent_incidents' => $recentIncidents,
            'active_assignments' => $activeAssignments,
            'assignments_completed_this_week' => $completedThisWeek,
            'sms_stats' => $smsStats,
            'analytics' => [
                'categories' => $categoryCounts,
                'barangays' => $barangayCounts,
                'weekly_trends' => $weeklyTrends,
                'avg_resolution_hours' => $avgResolutionHours,
                'agency_response_times' => $agencyResponseTimes,
                'seasonal_counts' => $seasonalCounts,
                'redundancy_hotspots' => $redundancyData,
            ],
        ]);
    }
}
