<?php

namespace App\Services;

use App\Enums\IncidentPriority;
use App\Enums\IncidentStatus;
use App\Models\Incident;
use App\Models\User;
use App\Repositories\Contracts\IncidentRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class IncidentService
{
    public function __construct(
        private readonly IncidentRepositoryInterface $incidents,
        private readonly TrackingNumberService $trackingNumbers,
        private readonly ActivityLogService $activityLogs,
        private readonly EvidenceService $evidenceService,
        private readonly NotificationService $notifications,
        private readonly GeofenceService $geofence,
    ) {}

    /**
     * @param  list<UploadedFile>  $evidenceFiles
     */
    public function submitAnonymousReport(array $data, array $evidenceFiles = []): Incident
    {
        $payload = Arr::except($data, ['evidence']);
        $meta = $payload['meta'] ?? [];

        if (isset($meta['gps_captures']) && is_string($meta['gps_captures'])) {
            $decoded = json_decode($meta['gps_captures'], true);
            $meta['gps_captures'] = is_array($decoded) ? $decoded : [];
        }

        $lat = isset($payload['latitude']) ? (float) $payload['latitude'] : null;
        $lng = isset($payload['longitude']) ? (float) $payload['longitude'] : null;
        $withinJurisdiction = $this->geofence->isWithinPamplona($lat, $lng);

        if ($withinJurisdiction !== null) {
            $meta['within_jurisdiction'] = $withinJurisdiction;
        }

        $resolvedBarangay = $this->geofence->resolveBarangay($lat, $lng);
        $payload['barangay'] = $resolvedBarangay ?? ($payload['barangay'] ?? null);

        $plainPin = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $gpsCaptures = $meta['gps_captures'] ?? [];

        // Keep the DB transaction short: create the case row only. Evidence
        // watermarking / Nominatim reverse-geocode runs after commit so a
        // multi-photo report cannot hold row locks for tens of seconds.
        $incident = DB::transaction(function () use ($payload, $meta, $plainPin) {
            $incident = $this->incidents->create([
                ...Arr::except($payload, ['meta']),
                'meta' => $meta ?: null,
                'tracking_number' => $this->trackingNumbers->generate(),
                'tracking_pin' => Hash::make($plainPin),
                'status' => IncidentStatus::Submitted,
                'priority' => $payload['priority'] ?? IncidentPriority::Medium->value,
                'reported_at' => $payload['reported_at'] ?? now(),
                'is_anonymous' => (bool) ($payload['is_anonymous'] ?? true),
            ]);

            $incident->statusUpdates()->create([
                'user_id' => null,
                'from_status' => null,
                'to_status' => IncidentStatus::Submitted,
                'comment' => 'Incident report received from the public portal.',
                'is_public' => true,
            ]);

            $this->activityLogs->log(
                description: 'Public incident report submitted.',
                subject: $incident,
                event: 'incident.submitted',
                logName: 'incident',
                properties: [
                    'tracking_number' => $incident->tracking_number,
                    'is_anonymous' => $incident->is_anonymous,
                ],
            );

            return $incident;
        });

        if ($evidenceFiles !== []) {
            $this->evidenceService->attachToIncident($incident, $evidenceFiles, $gpsCaptures);
        } else {
            $meta = $incident->meta ?? [];
            $meta['needs_verification'] = true;
            $meta['verification_reason'] = 'submitted_without_evidence';
            $incident->forceFill(['meta' => $meta])->save();
        }

        $this->linkCorroboratingCluster($incident);

        try {
            $this->notifications->notifyAdminNewIncident($incident);
        } catch (\Exception $e) {
            Log::warning('SMS alert to admin failed: '.$e->getMessage());
        }

        Cache::forget('admin.dashboard.json');

        $incident->load(['incidentType', 'evidence']);
        $incident->plainTrackingPin = $plainPin;

        return $incident;
    }

    public function findByTrackingNumber(string $trackingNumber): ?Incident
    {
        return $this->incidents->findByTrackingNumber($trackingNumber);
    }

    public function recordStatusChange(
        Incident $incident,
        IncidentStatus $toStatus,
        ?User $user = null,
        ?string $comment = null,
        bool $isPublic = true,
    ): Incident {
        return DB::transaction(function () use ($incident, $toStatus, $user, $comment, $isPublic) {
            $fromStatus = $incident->status;

            if ($fromStatus === $toStatus) {
                return $incident;
            }

            if (! $this->canTransitionTo($incident, $toStatus)) {
                throw new \InvalidArgumentException(sprintf(
                    'Illegal status transition from %s to %s for incident #%d.',
                    $fromStatus->value,
                    $toStatus->value,
                    $incident->id,
                ));
            }

            $incident->statusUpdates()->create([
                'user_id' => $user?->id,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'comment' => $comment,
                'is_public' => $isPublic,
            ]);

            $attributes = ['status' => $toStatus];

            if ($toStatus === IncidentStatus::Resolved) {
                $attributes['resolved_at'] = now();
            }

            if ($toStatus === IncidentStatus::Closed) {
                $attributes['closed_at'] = now();
                if ($incident->resolved_at === null) {
                    $attributes['resolved_at'] = now();
                }
            }

            $incident = $this->incidents->update($incident, $attributes);

            $this->activityLogs->log(
                description: sprintf('Incident status changed from %s to %s.', $fromStatus->value, $toStatus->value),
                user: $user,
                subject: $incident,
                event: 'incident.status_changed',
                logName: 'incident',
                properties: [
                    'from' => $fromStatus->value,
                    'to' => $toStatus->value,
                ],
            );

            if ($isPublic) {
                try {
                    $this->notifications->notifyReporterStatusUpdate($incident, $comment ?: 'Status is now '.$toStatus->label());
                } catch (\Exception $e) {
                    Log::warning('SMS alert to reporter failed: '.$e->getMessage());
                }
            }

            Cache::forget('admin.dashboard.json');

            return $incident;
        });
    }

    public function canTransitionTo(Incident $incident, IncidentStatus $targetStatus): bool
    {
        return in_array($targetStatus, $incident->status->availableTransitions());
    }

    /**
     * Records one assignee's independent acceptance. Every assignee gets logged;
     * the shared incident status only advances on the first acceptance so later
     * acceptances don't get blocked or hidden by an already-in-progress status.
     */
    public function logAssignmentAcknowledged(\App\Models\Assignment $assignment, Incident $incident, User $user): Incident
    {
        return DB::transaction(function () use ($assignment, $incident, $user) {
            $assignment->update([
                'acknowledged_at' => now(),
                'acknowledged_by' => $user->id,
            ]);

            $this->activityLogs->log(
                description: sprintf('%s acknowledged assignment for incident #%d.', $user->name, $incident->id),
                user: $user,
                subject: $incident,
                event: 'assignment.acknowledged',
                logName: 'incident',
                properties: ['assignment_id' => $assignment->id],
            );

            if ($incident->status === IncidentStatus::Assigned) {
                $incident = $this->recordStatusChange(
                    incident: $incident,
                    toStatus: IncidentStatus::InProgress,
                    user: $user,
                    comment: 'Assignment accepted and incident under active investigation',
                    isPublic: true,
                );
            }

            return $incident->fresh();
        });
    }

    /**
     * Link nearby same-type reports (~150m / ~30 min) as corroborating cluster.
     */
    private function linkCorroboratingCluster(Incident $incident): void
    {
        if ($incident->latitude === null || $incident->longitude === null) {
            return;
        }

        $lat = (float) $incident->latitude;
        $lng = (float) $incident->longitude;
        $radiusM = 150;
        $windowMinutes = 30;

        $candidates = Incident::query()
            ->where('id', '!=', $incident->id)
            ->where('incident_type_id', $incident->incident_type_id)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('reported_at', '>=', now()->subMinutes($windowMinutes))
            ->whereNotIn('status', [
                IncidentStatus::Closed->value,
                IncidentStatus::Rejected->value,
            ])
            ->limit(25)
            ->get();

        $linkedIds = [];
        foreach ($candidates as $other) {
            $distance = $this->haversineMeters(
                $lat,
                $lng,
                (float) $other->latitude,
                (float) $other->longitude,
            );
            if ($distance <= $radiusM) {
                $linkedIds[] = $other->id;
            }
        }

        if ($linkedIds === []) {
            return;
        }

        $meta = is_array($incident->meta) ? $incident->meta : [];
        $meta['cluster'] = [
            'linked_incident_ids' => $linkedIds,
            'radius_m' => $radiusM,
            'window_minutes' => $windowMinutes,
            'detected_at' => now()->toIso8601String(),
        ];
        $meta['redundancy_signal'] = true;
        $incident->forceFill(['meta' => $meta])->save();

        foreach ($linkedIds as $otherId) {
            $other = Incident::query()->find($otherId);
            if (! $other) {
                continue;
            }
            $otherMeta = is_array($other->meta) ? $other->meta : [];
            $existing = $otherMeta['cluster']['linked_incident_ids'] ?? [];
            if (! in_array($incident->id, $existing, true)) {
                $existing[] = $incident->id;
            }
            $otherMeta['cluster'] = [
                'linked_incident_ids' => array_values(array_unique($existing)),
                'radius_m' => $radiusM,
                'window_minutes' => $windowMinutes,
                'detected_at' => now()->toIso8601String(),
            ];
            $otherMeta['redundancy_signal'] = true;
            $other->forceFill(['meta' => $otherMeta])->save();
        }
    }

    private function haversineMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6371000.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $earth * asin(min(1, sqrt($a)));
    }
}
