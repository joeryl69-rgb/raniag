@props(['priority'])

@php
    $value = $priority instanceof \BackedEnum ? $priority->value : (string) $priority;
    $label = $priority instanceof \BackedEnum && method_exists($priority, 'label')
        ? $priority->label()
        : ucfirst($value);

    $data = match ($value) {
        'low' => ['class' => 'bg-info text-dark', 'icon' => 'bi-info-circle'],
        'medium' => ['class' => 'bg-warning text-dark', 'icon' => 'bi-exclamation-circle'],
        'high' => ['class' => 'bg-danger text-white', 'icon' => 'bi-exclamation-triangle-fill'],
        'critical' => ['class' => 'bg-dark text-white', 'icon' => 'bi-exclamation-octagon-fill'],
        default => ['class' => 'bg-secondary text-white', 'icon' => 'bi-record-circle'],
    };
@endphp

<span {{ $attributes->merge(['class' => "badge raniag-priority-badge {$data['class']} text-capitalize px-2 py-1"]) }}>
    <i class="bi {{ $data['icon'] }} me-1"></i>{{ $label }}
</span>
