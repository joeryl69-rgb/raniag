@php
    use App\Support\IconLibrary;

    $icon = IconLibrary::resolve($icon ?? ($type->icon ?? null));
    $color = IconLibrary::resolveColor($color ?? ($type->color ?? null));
    $size = $size ?? 38;
    $fs = $size >= 42 ? 'fs-5' : ($size <= 30 ? 'small' : '');
@endphp
<span
    class="raniag-type-badge d-inline-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
    style="width:{{ $size }}px;height:{{ $size }}px;background-color:{{ $color }}22;color:{{ $color }};"
    title="{{ $label ?? ($type->name ?? '') }}"
>
    <i class="bi {{ $icon }} {{ $fs }}"></i>
</span>
