@props(['status'])

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
        default => 'bg-secondary',
    };
@endphp

<span {{ $attributes->merge(['class' => "badge raniag-status-badge {$class}"]) }}>
    {{ $label }}
</span>
