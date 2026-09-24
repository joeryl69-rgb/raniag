@props([
    'incident',
    'assignment',
    'phaseRoute',
    'smsRoute',
])

@php
    $phase = $assignment?->field_phase ?? ($assignment?->isAcknowledged() ? 'accepted' : null);
    $steps = [
        'accepted' => ['label' => 'Accepted', 'icon' => 'bi-check2-circle'],
        'en_route' => ['label' => 'En route', 'icon' => 'bi-car-front-fill'],
        'on_scene' => ['label' => 'On scene', 'icon' => 'bi-geo-alt-fill'],
    ];
    $order = array_keys($steps);
    $currentIndex = array_search($phase, $order, true);
    $currentIndex = $currentIndex === false ? 0 : $currentIndex;

    // Next actionable phase (what the primary CTA advances to), if any.
    $nextKey = $order[$currentIndex + 1] ?? null;
    $canSms = ! $incident->is_anonymous && filled($incident->reporter_phone);
@endphp

@if ($assignment && $assignment->isAcknowledged() && ! in_array($incident->status->value, ['resolved', 'closed'], true))
<div class="rg-field-console mb-3">
    <div class="rg-field-console-head">
        <span class="rg-field-console-eyebrow">Live field status</span>
        @if ($incident->latitude && $incident->longitude)
            <a class="rg-field-nav-link"
               href="https://www.google.com/maps/dir/?api=1&destination={{ $incident->latitude }},{{ $incident->longitude }}"
               target="_blank" rel="noopener" title="Open turn-by-turn directions in Google Maps">
                <i class="bi bi-box-arrow-up-right me-1"></i>Open in Maps
            </a>
        @endif
    </div>

    {{-- Visual phase stepper: replaces the flat row of identical buttons with a
         clear "you are here, this is next" progression so it reads as a live
         status console rather than an arbitrary set of selection buttons. --}}
    <div class="rg-phase-stepper" id="field-phase-strip" data-field-phase="{{ $phase }}">
        @foreach ($steps as $key => $meta)
            @php $stepIndex = array_search($key, $order, true); @endphp
            <div class="rg-phase-step {{ $stepIndex < $currentIndex ? 'is-done' : ($stepIndex === $currentIndex ? 'is-current' : 'is-pending') }}">
                <div class="rg-phase-dot"><i class="bi {{ $stepIndex < $currentIndex ? 'bi-check-lg' : $meta['icon'] }}"></i></div>
                <div class="rg-phase-label">{{ $meta['label'] }}</div>
            </div>
            @if (!$loop->last)
                <div class="rg-phase-connector {{ $stepIndex < $currentIndex ? 'is-done' : '' }}"></div>
            @endif
        @endforeach
    </div>

    <div class="rg-gps-share" id="rg-gps-share-status" role="status">
        @if (in_array($phase, ['en_route', 'on_scene'], true))
            Tap My location on the map and allow the prompt. This device’s pin then moves on the case map, the admin case file, and the public tracking page.
        @else
            Tap My location on the map, allow the prompt, then mark En route. The truck pin moves here and on the public tracking page.
        @endif
    </div>

    <div class="rg-field-actions">
        @if ($nextKey)
            <form method="POST" action="{{ $phaseRoute }}" class="field-phase-form flex-grow-1">
                @csrf
                <input type="hidden" name="field_phase" value="{{ $nextKey }}">
                <button type="submit" class="btn btn-primary w-100 rg-field-cta">
                    <i class="bi {{ $steps[$nextKey]['icon'] }} me-1"></i>Mark "{{ $steps[$nextKey]['label'] }}"
                </button>
            </form>
        @else
            <div class="rg-field-done-hint flex-grow-1">
                <i class="bi bi-flag-fill me-1 text-success"></i>On scene — log your update and resolve the case below when finished.
            </div>
        @endif

        @if ($canSms)
            <button type="button" class="btn btn-outline-secondary rg-field-sms-toggle" data-bs-toggle="collapse" data-bs-target="#rgSmsPanel" aria-expanded="false">
                <i class="bi bi-chat-dots"></i>
            </button>
        @endif
    </div>

    @if ($canSms)
        <div class="collapse mt-2" id="rgSmsPanel">
            <form method="POST" action="{{ $smsRoute }}" class="field-sms-form rg-field-sms-form">
                @csrf
                <label class="form-label small fw-semibold">SMS reporter</label>
                <textarea name="message" class="form-control form-control-sm mb-2" rows="2" maxlength="480" required placeholder="Short update to the reporter…"></textarea>
                <input type="text" name="thread_note" class="form-control form-control-sm mb-2" maxlength="500" placeholder="Internal note (optional)">
                <button type="submit" class="btn btn-sm btn-outline-success"><i class="bi bi-chat-dots me-1"></i>Send SMS</button>
            </form>
        </div>
    @endif
</div>
@endif
