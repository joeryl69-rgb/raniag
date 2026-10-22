@props(['barangay' => null])

@php
    $name = trim((string) $barangay);
@endphp

@if (\App\Support\PlaceScope::isOfficial($name))
    <span>{{ $name }}</span>
@elseif ($name !== '')
    <span class="rg-place-outside">{{ $name }} <span class="rg-place-outside-tag">Outside Pamplona</span></span>
@else
    <span class="text-muted">—</span>
@endif
