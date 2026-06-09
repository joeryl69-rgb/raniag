<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ActivityLogService
{
    public function log(
        string $description,
        ?User $user = null,
        ?Model $subject = null,
        ?string $event = null,
        ?string $logName = null,
        array $properties = [],
        ?Request $request = null,
    ): ActivityLog {
        $request ??= request();

        return ActivityLog::query()->create([
            'user_id' => $user?->id,
            'log_name' => $logName,
            'description' => $description,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'event' => $event,
            'properties' => $properties ?: null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}
