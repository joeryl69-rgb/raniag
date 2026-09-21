<?php

namespace App\Http\Controllers\Admin;

use App\Enums\IncidentPriority;
use App\Enums\IncidentStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Incident;
use App\Models\User;
use App\Services\AssignmentService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DispatchQueueController extends Controller
{
    public function __construct(
        private readonly AssignmentService $assignments,
        private readonly NotificationService $notifications,
    ) {}

    public function index(): View
    {
        $slaHours = (int) config('raniag.sla_target_hours', 48);
        $priorityRank = [
            IncidentPriority::Critical->value => 4,
            IncidentPriority::High->value => 3,
            IncidentPriority::Medium->value => 2,
            IncidentPriority::Low->value => 1,
        ];

        $queue = Incident::query()
            ->with(['incidentType', 'assignments.agency'])
            ->whereIn('status', [
                IncidentStatus::Submitted->value,
                IncidentStatus::Received->value,
                IncidentStatus::Assigned->value,
                IncidentStatus::InProgress->value,
                IncidentStatus::PendingInfo->value,
            ])
            ->where('is_drill', false)
            ->orderByDesc('reported_at')
            ->limit(100)
            ->get()
            ->map(function (Incident $incident) use ($slaHours, $priorityRank) {
                $ageHours = $incident->reported_at
                    ? $incident->reported_at->diffInHours(now())
                    : 0;
                $slaRisk = $ageHours >= $slaHours ? 2 : ($ageHours >= ($slaHours * 0.75) ? 1 : 0);
                $p = $incident->priority instanceof IncidentPriority
                    ? $incident->priority->value
                    : (string) $incident->priority;

                return [
                    'incident' => $incident,
                    'age_hours' => $ageHours,
                    'sla_risk' => $slaRisk,
                    'priority_rank' => $priorityRank[$p] ?? 0,
                ];
            })
            ->sortByDesc(fn ($row) => [$row['sla_risk'], $row['priority_rank'], $row['age_hours']])
            ->values();

        return view('admin.dispatch.queue', [
            'queue' => $queue,
            'agencies' => Agency::query()->where('is_active', true)->orderBy('name')->get(),
            'slaHours' => $slaHours,
        ]);
    }

    public function assign(Request $request, Incident $incident): RedirectResponse
    {
        $data = $request->validate([
            'agency_id' => ['required', 'exists:agencies,id'],
        ]);

        $agency = Agency::query()->findOrFail($data['agency_id']);
        $this->assignments->assignToAgency($incident, $agency, $request->user());

        return back()->with('success', "Assigned {$incident->tracking_number} to {$agency->name}.");
    }

    public function toggleDrill(Request $request, Incident $incident): RedirectResponse
    {
        $incident->update(['is_drill' => ! $incident->is_drill]);

        return back()->with('success', $incident->is_drill ? 'Marked as drill/training.' : 'Drill flag cleared.');
    }

    public function broadcast(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'barangay' => ['required', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:480'],
        ]);

        $barangay = $data['barangay'];
        $staff = User::query()
            ->where('is_active', true)
            ->whereIn('role', [UserRole::Administrator, UserRole::Agency, UserRole::Personnel])
            ->get();

        foreach ($staff as $user) {
            $this->notifications->notify(
                $user,
                'broadcast.barangay',
                $data['title'],
                "[{$barangay}] {$data['message']}",
                null,
                ['barangay' => $barangay],
            );

            if ($user->phone) {
                $this->notifications->smsStaffDirect(
                    $user,
                    "RANIAG [{$barangay}]: {$data['title']} — {$data['message']}"
                );
            }
        }

        return back()->with('success', "Broadcast sent for barangay {$barangay}.");
    }
}
