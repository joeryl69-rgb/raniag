@php
    $sections = [
        'incident_details' => 'Incident Details',
        'narrative' => 'Narrative',
        'resolutions' => 'Resolution Notes',
        'status_timeline' => 'Status Timeline',
        'evidence_photos' => 'Evidence Photos',
        'call_taker_form' => 'Call Taker Form',
        'dispatch_form' => 'Dispatch Form',
        'narrative_report' => 'Narrative Report',
        'endorsement_sheet' => 'Endorsement Sheet',
    ];
@endphp
<div class="border rounded p-2 mt-1">
    <div class="form-check form-check-inline mb-1">
        <label class="small fw-semibold text-muted mb-0 me-2">Include in printable copy:</label>
        <span class="small text-muted">(leave all unchecked to include everything)</span>
    </div>
    <p class="small text-warning mb-2 d-none" id="{{ $idPrefix }}_availability_note"></p>
    <div class="d-flex flex-wrap gap-3">
        @foreach($sections as $value => $label)
            <div class="form-check form-check-sm" id="{{ $idPrefix }}_section_{{ $value }}_wrap">
                <input class="form-check-input" type="checkbox" name="requested_sections[]" value="{{ $value }}" id="{{ $idPrefix }}_section_{{ $value }}">
                <label class="form-check-label small" for="{{ $idPrefix }}_section_{{ $value }}">{{ $label }}</label>
            </div>
        @endforeach
    </div>
</div>
