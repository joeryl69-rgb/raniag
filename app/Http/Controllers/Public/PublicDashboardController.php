<?php

namespace App\Http\Controllers\Public;

use App\Enums\IncidentStatus;
use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Models\IncidentType;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Community-facing situational dashboard. Every query here is
 * aggregate-only or strips identifying fields before it leaves the
 * controller — no reporter name/phone/email, no exact street address,
 * no personnel/agency internal notes. Only tracking number, incident
 * type, barangay (not street-level address), priority, status, and
 * timestamps are ever exposed. This mirrors what a public transparency
 * board would show, not the internal ops view.
 */
class PublicDashboardController extends Controller
{
    public function index(): View
    {
        return view('public.dashboard');
    }

    public function data(): JsonResponse
    {
        $totalThisMonth = Incident::whereMonth('reported_at', now()->month)
            ->whereYear('reported_at', now()->year)
            ->count();

        $resolvedThisMonth = Incident::whereMonth('reported_at', now()->month)
            ->whereYear('reported_at', now()->year)
            ->whereIn('status', [IncidentStatus::Resolved->value, IncidentStatus::Closed->value])
            ->count();

        $statusCounts = Incident::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $typeCounts = IncidentType::withCount('incidents')
            ->having('incidents_count', '>', 0)
            ->orderByDesc('incidents_count')
            ->get()
            ->map(fn (IncidentType $t) => [
                'name' => $t->name,
                'icon' => $t->icon,
                'color' => $t->color,
                'count' => $t->incidents_count,
            ]);

        // Aggregated per-barangay counts only — never individual
        // coordinates or street addresses, so no single report can be
        // pinpointed to a specific household or person.
        $barangayCounts = Incident::selectRaw('barangay, COUNT(*) as count')
            ->whereNotNull('barangay')
            ->groupBy('barangay')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Last 6 months trend, resolved vs total, for a simple bar/line
        // chart — no per-incident detail.
        $monthlyTrend = collect(range(5, 0))->map(function (int $monthsAgo) {
            $month = now()->subMonths($monthsAgo);

            return [
                'label' => $month->format('M'),
                'total' => Incident::whereMonth('reported_at', $month->month)
                    ->whereYear('reported_at', $month->year)
                    ->count(),
                'resolved' => Incident::whereMonth('reported_at', $month->month)
                    ->whereYear('reported_at', $month->year)
                    ->whereIn('status', [IncidentStatus::Resolved->value, IncidentStatus::Closed->value])
                    ->count(),
            ];
        });

        // Recent activity feed — tracking number, type, barangay, status,
        // and a relative timestamp only. No reporter identity, no exact
        // address, no assigned personnel/agency name.
        $recentActivity = Incident::with('incidentType')
            ->orderByDesc('reported_at')
            ->limit(8)
            ->get()
            ->map(fn (Incident $i) => [
                'tracking_number' => $i->tracking_number,
                'type' => $i->incidentType?->name,
                'icon' => $i->incidentType?->icon,
                'color' => $i->incidentType?->color,
                'barangay' => $i->barangay,
                'status' => $i->status instanceof \BackedEnum ? $i->status->value : (string) $i->status,
                'reported_at' => $i->reported_at?->diffForHumans(),
            ]);

        return response()->json([
            'total_this_month' => $totalThisMonth,
            'resolved_this_month' => $resolvedThisMonth,
            'total_all_time' => Incident::count(),
            'status_counts' => $statusCounts,
            'type_counts' => $typeCounts,
            'barangay_counts' => $barangayCounts,
            'monthly_trend' => $monthlyTrend,
            'recent_activity' => $recentActivity,
            'generated_at' => now()->toIso8601String(),
        ]);
    }
}
