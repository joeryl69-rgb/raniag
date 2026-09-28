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
        'en_route' => ['label' => 'En route', 'icon' => 'bi-person-walking'],
        'on_scene' => ['label' => 'On scene', 'icon' => 'bi-geo-alt-fill'],
    ];
    $order = array_keys($steps);
    $currentIndex = array_search($phase, $order, true);
    $currentIndex = $currentIndex === false ? 0 : $currentIndex;

    // Next actionable phase (what the primary CTA advances to), if any.
    $nextKey = $order[$currentIndex + 1] ?? null;
    // A GPS-camera photo or video lets the reporter stay anonymous. Without
    // one they had to leave their name and a phone or email, so the text
    // box is only offered for that identified report.
    $identifiedWithoutGps = ! $incident->reporterSubmittedGpsCamera() && ! $incident->is_anonymous;
    $canSms = $identifiedWithoutGps && filled($incident->safeReporterPhone());
    $reporterTexts = $identifiedWithoutGps ? $incident->reporterTextMessages() : collect();
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
        @if ($phase === 'on_scene')
            You are on scene. This device keeps sharing location until the case is closed.
        @elseif ($phase === 'en_route')
            Live location is sharing. On scene is set automatically when this device reaches the incident.
        @else
            Mark En route to turn location on. The pin and route then update on this map and on the public tracking page.
        @endif
    </div>

    @if ($identifiedWithoutGps)
        <div class="alert alert-warning d-flex gap-2 align-items-start small mb-2 py-2" role="status">
            <i class="bi bi-person-badge mt-1"></i>
            <div>
                <strong>Reporter is not anonymous.</strong>
                No GPS camera photo or video was submitted, so they had to leave their contact details.
                @if ($canSms)
                    Use the message button to text the number on the report.
                @else
                    No phone number is on file. Use the email on the case if you need to reach them.
                @endif
            </div>
        </div>
    @endif

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

    @if ($reporterTexts->isNotEmpty())
        <div class="mt-2 mb-1">
            <h6 class="fw-bold mb-2">Texts to the reporter</h6>
            <div class="small border rounded p-2 bg-white" style="max-height:180px;overflow:auto;">
                @foreach ($reporterTexts as $sms)
                    <div class="mb-2">
                        <div class="text-muted">{{ $sms->created_at?->timezone(config('app.timezone'))->format('M j, g:ia') }} · {{ $sms->status?->value ?? $sms->status }}</div>
                        <div>{{ $sms->message }}</div>
                        @if ($sms->thread_note)
                            <div class="fst-italic text-muted">Staff note: {{ $sms->thread_note }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if ($canSms)
        <div class="collapse mt-2" id="rgSmsPanel">
            <form method="POST" action="{{ $smsRoute }}" class="field-sms-form rg-field-sms-form">
                @csrf
                <label class="form-label small fw-semibold" for="reporter-sms-message">Text message to the reporter</label>
                <p class="form-text mt-0 mb-2">This is sent to the phone number on the report. Use it for a short update such as “We are on the way” or “Please stay indoors.” It does not appear on the public map.</p>
                <textarea id="reporter-sms-message" name="message" class="form-control form-control-sm mb-2" rows="2" maxlength="480" required placeholder="We are on the way. Stay clear of the area."></textarea>
                <label class="form-label small fw-semibold" for="reporter-sms-note">Staff note (optional)</label>
                <p class="form-text mt-0 mb-2">Saved on the case for your team only. The reporter does not receive this.</p>
                <input id="reporter-sms-note" type="text" name="thread_note" class="form-control form-control-sm mb-2" maxlength="500" placeholder="Example: family is waiting at the chapel">
                <button type="submit" class="btn btn-sm btn-outline-success"><i class="bi bi-chat-dots me-1"></i>Send text message</button>
            </form>
        </div>
    @endif
</div>
@endif
