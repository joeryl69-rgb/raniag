<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Incident;
use App\Services\ActivityLogService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ResponderFieldController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLogs,
        private readonly NotificationService $notifications,
    ) {}

    public function updateFieldPhase(Request $request, Incident $incident): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'field_phase' => ['required', 'in:accepted,en_route,on_scene'],
        ]);

        $assignment = $this->activeAssignment($request, $incident);
        abort_if(! $assignment, 403);
        abort_if(! $assignment->isAcknowledged(), 422, 'Accept the assignment first.');

        $assignment->update(['field_phase' => $data['field_phase']]);

        $labels = [
            'accepted' => 'Assignment accepted',
            'en_route' => 'Responder en route',
            'on_scene' => 'Responder on scene',
        ];
        $label = $labels[$data['field_phase']];

        $this->activityLogs->log(
            description: $label.' for incident #'.$incident->id,
            user: $request->user(),
            subject: $incident,
            event: 'assignment.field_phase',
            logName: 'incident',
            properties: ['field_phase' => $data['field_phase'], 'assignment_id' => $assignment->id],
        );

        $incident->statusUpdates()->create([
            'user_id' => $request->user()->id,
            'from_status' => $incident->status,
            'to_status' => $incident->status,
            'comment' => $label,
            'is_public' => true,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'field_phase' => $data['field_phase']]);
        }

        return back()->with('success', $label.'.');
    }

    public function smsReporter(Request $request, Incident $incident): RedirectResponse|JsonResponse
    {
        abort_if($incident->is_anonymous || ! $incident->reporter_phone, 422, 'No reporter phone on file.');

        $data = $request->validate([
            'message' => ['required', 'string', 'max:480'],
            'thread_note' => ['nullable', 'string', 'max:500'],
        ]);

        $this->notifications->smsReporterFromStaff(
            incident: $incident,
            message: $data['message'],
            user: $request->user(),
            threadNote: $data['thread_note'] ?? null,
        );

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'SMS queued to reporter.');
    }

    private function activeAssignment(Request $request, Incident $incident): ?Assignment
    {
        $user = $request->user();

        return Assignment::query()
            ->where('incident_id', $incident->id)
            ->where('is_active', true)
            ->where(function ($q) use ($user) {
                if ($user->isPersonnel()) {
                    $q->where('assigned_to', $user->id);
                    if ($user->agency_id) {
                        $q->orWhere('agency_id', $user->agency_id);
                    }

                    return;
                }

                $q->where('agency_id', $user->agency_id);
            })
            ->latest('created_at')
            ->first();
    }
}
