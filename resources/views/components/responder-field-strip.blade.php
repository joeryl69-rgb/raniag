@props([
    'incident',
    'assignment',
    'phaseRoute',
    'smsRoute',
])

@php
    $phase = $assignment?->field_phase ?? ($assignment?->isAcknowledged() ? 'accepted' : null);
    $steps = [
        'accepted' => 'Accepted',
        'en_route' => 'En route',
        'on_scene' => 'On scene',
    ];
    $canSms = ! $incident->is_anonymous && filled($incident->reporter_phone);
@endphp

@if ($assignment && $assignment->isAcknowledged() && ! in_array($incident->status->value, ['resolved', 'closed'], true))
<div class="card raniag-card mb-3 border-0 shadow-sm">
    <div class="card-body p-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
            <div class="fw-semibold small text-uppercase text-muted">Field status</div>
            @if ($incident->latitude && $incident->longitude)
                <a class="btn btn-sm btn-outline-primary"
                   href="https://www.google.com/maps/dir/?api=1&destination={{ $incident->latitude }},{{ $incident->longitude }}"
                   target="_blank" rel="noopener">
                    <i class="bi bi-navigation me-1"></i>Navigate
                </a>
            @endif
        </div>
        <div class="d-flex flex-wrap gap-2" id="field-phase-strip">
            @foreach ($steps as $key => $label)
                <form method="POST" action="{{ $phaseRoute }}" class="field-phase-form">
                    @csrf
                    <input type="hidden" name="field_phase" value="{{ $key }}">
                    <button type="submit"
                            class="btn btn-sm {{ $phase === $key ? 'btn-primary' : 'btn-outline-secondary' }}"
                            @disabled($phase === $key)>
                        {{ $label }}
                    </button>
                </form>
            @endforeach
            <span class="badge text-bg-light align-self-center">Then resolve below</span>
        </div>

        @if ($canSms)
            <hr class="my-3">
            <form method="POST" action="{{ $smsRoute }}" class="field-sms-form">
                @csrf
                <label class="form-label small fw-semibold">SMS reporter</label>
                <textarea name="message" class="form-control form-control-sm mb-2" rows="2" maxlength="480" required placeholder="Short update to the reporter…"></textarea>
                <input type="text" name="thread_note" class="form-control form-control-sm mb-2" maxlength="500" placeholder="Internal note (optional)">
                <button type="submit" class="btn btn-sm btn-outline-success"><i class="bi bi-chat-dots me-1"></i>Send SMS</button>
            </form>
        @endif
    </div>
</div>
@endif
