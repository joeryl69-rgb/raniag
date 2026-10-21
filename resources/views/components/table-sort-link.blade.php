@props([
    'label',
    'column',
    'route',
    'sortName' => 'sort',
    'directionName' => 'direction',
    'defaultSort' => 'created_at',
    'defaultDirection' => 'desc',
])

@php
    $active = request($sortName, $defaultSort) === $column;
    $currentDirection = request($directionName, $defaultDirection);
    $direction = $active
        ? ($currentDirection === 'asc' ? 'desc' : 'asc')
        : $defaultDirection;
    $query = array_merge(request()->query(), [
        $sortName => $column,
        $directionName => $direction,
        'page' => null,
        'agency_page' => null,
        'personnel_page' => null,
    ]);
    $icon = $active
        ? ($currentDirection === 'asc' ? 'bi-sort-up' : 'bi-sort-down')
        : 'bi-arrow-down-up';
@endphp

<a class="rg-sort-link" href="{{ route($route, $query) }}" aria-label="Sort by {{ $label }}">
    <span>{{ $label }}</span>
    <i class="bi {{ $icon }}" aria-hidden="true"></i>
</a>
