@props([
    'barangays' => [],
    'selected' => null,
    'allValue' => 'all',
    'allLabel' => 'All places',
])

@php
    $groups = \App\Support\PlaceScope::partition($barangays);
    $selected = (string) ($selected ?? '');
@endphp

<option value="{{ $allValue }}" @selected($selected === (string) $allValue)>{{ $allLabel }}</option>
@if ($groups['inside'] !== [])
    <optgroup label="Pamplona">
        @foreach ($groups['inside'] as $name)
            <option value="{{ $name }}" @selected($selected === $name)>{{ $name }}</option>
        @endforeach
    </optgroup>
@endif
@if ($groups['outside'] !== [])
    <optgroup label="Outside Pamplona">
        <option value="outside" @selected($selected === 'outside')>All outside Pamplona</option>
        @foreach ($groups['outside'] as $name)
            <option value="{{ $name }}" @selected($selected === $name)>{{ $name }}</option>
        @endforeach
    </optgroup>
@endif
