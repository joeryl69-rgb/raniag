@props(['status', 'comment' => null])

@php
    $value = $status instanceof \BackedEnum ? $status->value : (string) $status;
    $label = $status instanceof \BackedEnum ? $status->label() : ucfirst(str_replace('_', ' ', $value));

    $class = match ($value) {
        'submitted', 'received' => 'bg-secondary',
        'assigned', 'in_progress' => 'bg-primary',
        'pending_info' => 'bg-warning text-dark',
        'resolved' => 'bg-success',
        'closed' => 'bg-dark',
        'rejected' => 'bg-danger',
        'outside_aor' => 'bg-info text-dark',
        default => 'bg-secondary',
    };

    // A timeline entry's badge otherwise just echoes the raw to_status —
    // which reads as a contradiction on the exact log line whose comment
    // says "Resolution submitted; awaiting other agencies to complete."
    // while the badge still says the generic "In Progress". $comment (only
    // passed by timeline entries, never by the incident's own current-
    // status badge) lets us special-case that specific wording without
    // touching the underlying status enum or the incident's real state.
    if ($value === 'in_progress' && str_starts_with((string) $comment, 'Resolution submitted')) {
        $label = 'Resolution Submitted (Partial)';
        $class = 'bg-primary';
    } elseif ($value === 'pending_info' && str_starts_with((string) $comment, 'Admin reply:')) {
        $label = 'Admin Replied';
        $class = 'bg-info';
    }
@endphp

<span {{ $attributes->merge(['class' => "badge raniag-status-badge {$class}"]) }}>
    {{ $label }}
</span>
