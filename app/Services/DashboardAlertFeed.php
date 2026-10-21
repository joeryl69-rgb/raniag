<?php

namespace App\Services;

use App\Models\Incident;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class DashboardAlertFeed
{
    private const TYPES = ['incident.new', 'incident.assigned'];

    private const LIMIT = 10;

    public function cursorFor(User $user): int
    {
        return (int) Notification::query()
            ->where('user_id', $user->id)
            ->whereIn('type', self::TYPES)
            ->max('id');
    }

    /**
     * @return array{items: list<array<string, mixed>>, pending: list<array<string, mixed>>, cursor: int, pending_count: int}
     */
    public function after(User $user, int $cursor): array
    {
        $cursor = max(0, $cursor);
        $query = Notification::query()
            ->with(['incident.incidentType'])
            ->where('user_id', $user->id)
            ->whereIn('type', self::TYPES)
            ->whereNull('read_at');

        $notifications = (clone $query)
            ->where('id', '>', $cursor)
            ->orderBy('id')
            ->limit(self::LIMIT)
            ->get();
        $pendingNotifications = (clone $query)
            ->where('id', '<=', $cursor)
            ->orderBy('id')
            ->limit(self::LIMIT)
            ->get();

        return [
            'items' => $this->transform($notifications),
            'pending' => $this->transform($pendingNotifications),
            'cursor' => $notifications->isNotEmpty()
                ? (int) $notifications->last()->id
                : $cursor,
            'pending_count' => Notification::query()
                ->where('user_id', $user->id)
                ->whereIn('type', self::TYPES)
                ->whereNull('read_at')
                ->count(),
        ];
    }

    /**
     * @param  Collection<int, Notification>  $notifications
     * @return list<array<string, mixed>>
     */
    private function transform(Collection $notifications): array
    {
        return $notifications->map(function (Notification $notification): ?array {
            $incident = $notification->incident;
            if (! $incident instanceof Incident) {
                return null;
            }

            return [
                'id' => $notification->id,
                'type' => $notification->type,
                'title' => $notification->title,
                'incident_id' => $incident->id,
                'tracking_number' => $incident->tracking_number,
                'incident_type' => $incident->incidentType?->name,
                'barangay' => $incident->barangay,
                'priority' => $incident->priority instanceof \BackedEnum
                    ? $incident->priority->value
                    : (string) $incident->priority,
                'status' => $incident->status instanceof \BackedEnum
                    ? $incident->status->value
                    : (string) $incident->status,
                'created_at' => $notification->created_at?->toIso8601String(),
            ];
        })->filter()->values()->all();
    }
}
