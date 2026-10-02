<?php

namespace App\Http\Controllers\Public;

use App\Enums\IncidentStatus;
use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Support\PeriodRange;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
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

    public function data(Request $request): JsonResponse
    {
        $range = PeriodRange::resolve($request->query('period'));
        $completed = [IncidentStatus::Resolved->value, IncidentStatus::Closed->value];

        $insidePamplona = function (?Carbon $from = null, ?Carbon $to = null): Builder {
            $query = Incident::query()
                ->whereIn('barangay', config('raniag.barangays', []), 'and', false)
                ->where('status', '!=', IncidentStatus::OutsideAor->value)
                ->where(function (Builder $query) {
                    $query->whereNull('meta->within_jurisdiction')
                        ->orWhere('meta->within_jurisdiction', true);
                });

            if ($from && $to) {
                $query->whereBetween('reported_at', [$from, $to]);
            }

            return $query;
        };

        $periodQuery = fn (): Builder => $insidePamplona($range['from'], $range['to']);
        $previousQuery = fn (): Builder => $insidePamplona($range['previous_from'], $range['previous_to']);

        $totalThisPeriod = $periodQuery()->count();
        $resolvedThisPeriod = $periodQuery()->whereIn('status', $completed)->count();
        $totalPrevious = $previousQuery()->count();
        $resolvedPrevious = $previousQuery()->whereIn('status', $completed)->count();

        $statusCounts = $insidePamplona()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $typeCounts = $periodQuery()
            ->join('incident_types', 'incidents.incident_type_id', '=', 'incident_types.id')
            ->selectRaw('incident_types.name, incident_types.icon, incident_types.color, COUNT(*) as count')
            ->groupBy('incident_types.id', 'incident_types.name', 'incident_types.icon', 'incident_types.color')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($type) => [
                'name' => $type->name,
                'icon' => $type->icon,
                'color' => $type->color,
                'count' => (int) $type->count,
            ]);

        $barangayCounts = $periodQuery()
            ->selectRaw('barangay, COUNT(*) as count')
            ->whereNotNull('barangay')
            ->groupBy('barangay')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $monthlyTrend = collect($range['buckets'])->map(function (array $bucket) use ($insidePamplona, $completed) {
            return [
                'label' => $bucket['label'],
                'total' => $insidePamplona($bucket['from'], $bucket['to'])->count(),
                'resolved' => $insidePamplona($bucket['from'], $bucket['to'])->whereIn('status', $completed)->count(),
            ];
        });

        $recentActivity = $periodQuery()
            ->with('incidentType')
            ->orderByDesc('reported_at')
            ->limit(8)
            ->get()
            ->map(fn (Incident $i) => [
                'type' => $i->incidentType?->name,
                'icon' => $i->incidentType?->icon,
                'color' => $i->incidentType?->color,
                'barangay' => $i->barangay,
                'status' => $i->status instanceof \BackedEnum ? $i->status->value : (string) $i->status,
                'reported_at' => $i->reported_at?->diffForHumans(),
            ]);

        $hotspots = $this->hotspots($periodQuery());

        return response()->json([
            'period' => [
                'key' => $range['key'],
                'label' => $range['label'],
                'caption' => $range['caption'],
            ],
            'total_this_month' => $totalThisPeriod,
            'resolved_this_month' => $resolvedThisPeriod,
            'reports_change' => PeriodRange::change($totalThisPeriod, $totalPrevious),
            'resolved_change' => PeriodRange::change($resolvedThisPeriod, $resolvedPrevious),
            'total_all_time' => $insidePamplona()->count(),
            'status_counts' => $statusCounts,
            'type_counts' => $typeCounts,
            'barangay_counts' => $barangayCounts,
            'monthly_trend' => $monthlyTrend,
            'most_active' => $hotspots['most_active'],
            'repeat_areas' => $hotspots['repeat_areas'],
            'recent_activity' => $recentActivity,
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Barangay + incident type pairs for the selected window.
     * Repeat areas are the ones the same incident comes back to.
     *
     * @return array{most_active: ?array, repeat_areas: list<array<string, mixed>>}
     */
    private function hotspots(Builder $query): array
    {
        $pairs = (clone $query)
            ->join('incident_types', 'incidents.incident_type_id', '=', 'incident_types.id')
            ->whereNotNull('incidents.barangay')
            ->selectRaw('incidents.barangay as barangay, incident_types.name as type, COUNT(*) as count')
            ->groupBy('incidents.barangay', 'incident_types.name')
            ->orderByDesc('count')
            ->get();

        $repeatAreas = $pairs
            ->filter(fn ($row) => (int) $row->count >= 2)
            ->take(6)
            ->map(fn ($row) => [
                'barangay' => $row->barangay,
                'type' => $row->type,
                'count' => (int) $row->count,
                'band' => PeriodRange::band((int) $row->count),
            ])
            ->values();

        $top = $pairs->first();

        return [
            'most_active' => $top ? [
                'barangay' => $top->barangay,
                'type' => $top->type,
                'count' => (int) $top->count,
                'band' => PeriodRange::band((int) $top->count),
            ] : null,
            'repeat_areas' => $repeatAreas->all(),
        ];
    }
}
