@props(['change' => null, 'note' => 'vs previous'])

@php
    $direction = $change['direction'] ?? 'flat';
    $percent = (int) ($change['percent'] ?? 0);
    $icon = $direction === 'up' ? 'bi-arrow-up-short' : ($direction === 'down' ? 'bi-arrow-down-short' : 'bi-dash');
@endphp

<span {{ $attributes->merge(['class' => 'stat-trend '.$direction]) }}>
    <i class="bi {{ $icon }}"></i>{{ abs($percent) }}% {{ $note }}
</span>
