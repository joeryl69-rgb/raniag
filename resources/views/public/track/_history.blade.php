@if ($incident->publicTimeline->isEmpty())
    <p class="text-muted mb-0">No public updates yet. Please check back later.</p>
@else
    <div class="raniag-timeline raniag-timeline-wide">
        @foreach ($incident->publicTimeline as $update)
            @php
                $tKey = $update->to_status->value ?? $update->to_status;
                $comment = trim((string) $update->comment);
                $actor = $update->user?->display_title;
                $agencyName = $update->user?->agency?->name;
                $byline = collect([$actor, $agencyName])->filter()->unique()->implode(' · ');
                $detail = $comment;
                if ($comment === 'Incident report received from the public portal.') {
                    $detail = 'The report was sent from the public form and is waiting for MDRRMO to review it.';
                } elseif ($comment === 'Report validated and received') {
                    $detail = 'MDRRMO Pamplona checked the report and opened it for response.';
                } elseif ($comment === 'Assignment accepted and incident under active investigation') {
                    $detail = ($agencyName ?: 'The assigned team').' accepted the assignment and started the investigation.';
                } elseif ($comment === 'Responder en route') {
                    $detail = ($agencyName ?: 'A responder').' is on the way to the reported location.';
                } elseif ($comment === 'Responder on scene') {
                    $detail = ($agencyName ?: 'A responder').' has arrived at the reported location.';
                } elseif (preg_match('/^Assigned to:?\s*(.+)$/', $comment, $assignedMatch)) {
                    $detail = 'MDRRMO assigned this report to '.rtrim($assignedMatch[1], '.').'.';
                } elseif (str_starts_with($comment, 'Reporter reply:')) {
                    $detail = 'You replied: '.trim(substr($comment, strlen('Reporter reply:')));
                }
            @endphp
            <div class="raniag-timeline-item" data-status="{{ $tKey }}">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-1">
                    <x-public.status-badge :status="$update->to_status" :comment="$update->comment" />
                    <small class="text-muted">{{ $update->created_at->format('M d, Y h:i A') }}</small>
                </div>
                @if ($byline)
                    <div class="rg-track-history-by">Recorded by {{ $byline }}</div>
                @endif
                @if ($detail !== '')
                    <p class="rg-track-history-copy">{{ $detail }}</p>
                @endif
            </div>
        @endforeach
    </div>
@endif

@if ($incident->resolutions->isNotEmpty())
    <h2 class="h6 fw-bold mt-4 mb-3">Agency resolutions</h2>
    <div class="raniag-timeline raniag-timeline-wide">
        @foreach ($incident->resolutions as $resolution)
            <div class="raniag-timeline-item">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-1">
                    <strong>{{ $resolution->resolver?->agency?->name ?? $resolution->resolver?->display_title ?? 'Response team' }}</strong>
                    <small class="text-muted">{{ ($resolution->resolved_at ?? $resolution->created_at)->format('M d, Y h:i A') }}</small>
                </div>
                <p class="rg-track-history-copy">{{ $resolution->summary }}</p>
            </div>
        @endforeach
    </div>
@endif
