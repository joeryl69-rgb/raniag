@props([
    'src',
    'label' => 'Evidence video',
    'variant' => '',
])

<div class="rg-evidence-video-player {{ $variant ? 'rg-evidence-video-player--'.$variant : '' }}" data-rg-evidence-player>
    <button
        type="button"
        class="rg-evidence-video-trigger"
        data-rg-evidence-video
        data-video-src="{{ $src }}"
        data-video-label="{{ $label }}"
        aria-label="Play {{ $label }}"
    >
        <span class="rg-evidence-video-trigger__preview" aria-hidden="true">
            <i class="bi bi-camera-video-fill"></i>
        </span>
        <span class="rg-evidence-video-trigger__action">
            <i class="bi bi-play-fill" aria-hidden="true"></i>
            <span>Play video</span>
        </span>
    </button>
</div>
