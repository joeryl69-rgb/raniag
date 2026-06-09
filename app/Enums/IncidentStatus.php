<?php

namespace App\Enums;

enum IncidentStatus: string
{
    case Submitted = 'submitted';
    case Received = 'received';
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case PendingInfo = 'pending_info';
    case Resolved = 'resolved';
    case Closed = 'closed';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Submitted',
            self::Received => 'Received',
            self::Assigned => 'Assigned',
            self::InProgress => 'In Progress',
            self::PendingInfo => 'Pending Information',
            self::Resolved => 'Resolved',
            self::Closed => 'Closed',
            self::Rejected => 'Rejected',
        };
    }

    public function availableTransitions(): array
    {
        return match ($this) {
            self::Submitted => [self::Received, self::Rejected],
            self::Received => [self::Assigned],
            self::Assigned => [self::InProgress, self::Rejected],
            self::InProgress => [self::PendingInfo, self::Resolved],
            self::PendingInfo => [self::InProgress, self::Resolved],
            self::Resolved => [self::Closed],
            self::Closed => [],
            self::Rejected => [],
        };
    }
}
