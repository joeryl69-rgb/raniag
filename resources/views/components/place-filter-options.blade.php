@props([
    'selected' => null,
    'allValue' => 'all',
])

@php
    $selected = (string) ($selected ?? '');
@endphp

<option value="{{ $allValue }}" @selected($selected === (string) $allValue)>All barangays</option>
<option value="outside" @selected($selected === 'outside')>All outside Pamplona</option>
<option value="inside" @selected($selected === 'inside')>All inside Pamplona</option>
