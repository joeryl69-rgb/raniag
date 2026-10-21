@props([
    'eyebrow',
    'title',
    'description',
    'icon' => 'bi-compass',
    'variant' => null,
])

<section class="rg-workspace-intro {{ $variant ? 'rg-workspace-intro--'.$variant : '' }}" aria-labelledby="rg-workspace-title">
    <div class="rg-workspace-intro-mark" aria-hidden="true"><i class="bi {{ $icon }}"></i></div>
    <div class="rg-workspace-intro-copy">
        <span>{{ $eyebrow }}</span>
        <h1 id="rg-workspace-title">{{ $title }}</h1>
        @if (filled($description))
            <p>{{ $description }}</p>
        @endif
    </div>
    @if (isset($slot) && trim((string) $slot) !== '')
        <div class="rg-workspace-intro-action">{{ $slot }}</div>
    @endif
</section>
