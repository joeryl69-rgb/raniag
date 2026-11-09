<div id="case-assignment-status">
    @if ($incident->assignments->isNotEmpty())
        @php
            $phaseLabels = [
                'accepted' => 'Accepted',
                'en_route' => 'En route',
                'on_scene' => 'On scene',
            ];
        @endphp
        <div class="track-agency-sheet mb-4">
            <div class="sheet-head">
                <h2 class="sheet-title"><i class="bi bi-people-fill me-1"></i>Responding agencies</h2>
                <span class="badge text-bg-light border">{{ $incident->assignments->count() }}</span>
            </div>
            @foreach ($incident->assignments as $assignment)
                @php
                    $phase = $assignment->field_phase
                        ?? ($assignment->isAcknowledged() ? 'accepted' : null);
                    $phaseClass = $phase ? 'is-'.$phase : 'is-pending';
                    $phaseText = $phase
                        ? ($phaseLabels[$phase] ?? ucfirst(str_replace('_', ' ', $phase)))
                        : 'Awaiting acceptance';
                @endphp
                <div class="track-agency-row" data-unit-name="{{ $assignment->agency?->name ?? $assignment->assignee?->display_title ?? 'Personnel' }}">
                    <div class="d-flex align-items-start gap-2">
                        <span class="rg-unit-pin rg-unit-pin-inline" title="{{ $phaseText }}"><i class="bi bi-broadcast-pin"></i></span>
                        <div>
                            <div class="agency-name">{{ $assignment->agency?->name ?? $assignment->assignee?->display_title ?? 'Personnel' }}</div>
                            <div class="agency-meta agency-eta">
                                @if (in_array($phase, ['en_route', 'on_scene'], true))
                                    ETA appears when this unit is sharing location
                                @endif
                            </div>
                            <div class="agency-meta">
                                @if ($assignment->acknowledged_at)
                                    Accepted {{ $assignment->acknowledged_at->diffForHumans() }}
                                @elseif ($assignment->assigned_at)
                                    Assigned {{ $assignment->assigned_at->diffForHumans() }}
                                @else
                                    Assigned to this report
                                @endif
                            </div>
                        </div>
                    </div>
                    <span class="track-phase-badge {{ $phase ? 'is-'.$phase : 'is-pending' }}">{{ $phaseText }}</span>
                </div>
            @endforeach
        </div>
    @endif
</div>
