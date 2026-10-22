@php
    $options = is_array($chapter['options'] ?? null) ? $chapter['options'] : [];
    $locked = $locked ?? false;
@endphp
@if($chapter['type'] === 'open_cases')
    @unless($locked)
        <input type="hidden" name="chapters[{{ $index }}][options][aor_scope]" value="{{ $options['aor_scope'] ?? 'aor_only' }}">
    @endunless
    <div class="row g-2">
        <div class="col-md-4">
            <label class="form-label">Barangay</label>
            <select class="form-select form-select-sm" name="chapters[{{ $index }}][options][barangay]" @disabled($locked)>
                <x-place-filter-options :selected="$options['barangay'] ?? ''" all-value="" />
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Office</label>
            <select class="form-select form-select-sm" name="chapters[{{ $index }}][options][agency_id]" @disabled($locked)>
                <option value="">All offices</option>
                @foreach($agencies as $agency)
                    <option value="{{ $agency->id }}" @selected((string) ($options['agency_id'] ?? '') === (string) $agency->id)>{{ $agency->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Incident type</label>
            <select class="form-select form-select-sm" name="chapters[{{ $index }}][options][incident_type_id]" @disabled($locked)>
                <option value="">All types</option>
                @foreach($incidentTypes as $type)
                    <option value="{{ $type->id }}" @selected((string) ($options['incident_type_id'] ?? '') === (string) $type->id)>{{ $type->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
@elseif($chapter['type'] === 'response')
    <div class="row g-2">
        <div class="col-md-4">
            <label class="form-label">Group the days by</label>
            <select class="form-select form-select-sm" name="chapters[{{ $index }}][options][view_mode]" @disabled($locked)>
                @foreach(['weekly' => 'Week', 'monthly' => 'Month', 'yearly' => 'Year', 'periodic' => 'The whole date range'] as $value => $label)
                    <option value="{{ $value }}" @selected(($options['view_mode'] ?? 'weekly') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-8 d-flex align-items-end">
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="chapters[{{ $index }}][options][compare]" value="1" id="compare-{{ $index }}" data-compare-toggle @checked(filter_var($options['compare'] ?? false, FILTER_VALIDATE_BOOLEAN)) @disabled($locked)>
                <label class="form-check-label" for="compare-{{ $index }}">Compare with an earlier period</label>
            </div>
        </div>
        <div class="col-md-4" data-compare-fields>
            <label class="form-label">Earlier from</label>
            <input type="date" class="form-control form-control-sm" name="chapters[{{ $index }}][options][compare_from]" value="{{ $options['compare_from'] ?? '' }}" max="{{ now()->toDateString() }}" @disabled($locked)>
        </div>
        <div class="col-md-4" data-compare-fields>
            <label class="form-label">Earlier to</label>
            <input type="date" class="form-control form-control-sm" name="chapters[{{ $index }}][options][compare_to]" value="{{ $options['compare_to'] ?? '' }}" max="{{ now()->toDateString() }}" @disabled($locked)>
        </div>
    </div>
@elseif($chapter['type'] === 'shelter')
    <label class="form-label">Who is on the roll</label>
    <select class="form-select form-select-sm" name="chapters[{{ $index }}][options][guest_scope]" @disabled($locked)>
        <option value="still_in" @selected(($options['guest_scope'] ?? '') === 'still_in')>Still inside</option>
        <option value="checked_in" @selected(($options['guest_scope'] ?? '') === 'checked_in')>Checked in during these dates</option>
        <option value="outside" @selected(($options['guest_scope'] ?? '') === 'outside')>Guests from outside Pamplona</option>
    </select>
@elseif($chapter['type'] === 'papers')
    <div class="row g-2">
        <div class="col-md-6">
            <label class="form-label">Paper</label>
            <select class="form-select form-select-sm" name="chapters[{{ $index }}][options][document_type]" @disabled($locked)>
                <option value="all" @selected(($options['document_type'] ?? 'all') === 'all')>All four forms</option>
                @foreach(\App\Enums\IncidentDocumentType::cases() as $paper)
                    <option value="{{ $paper->value }}" @selected(($options['document_type'] ?? '') === $paper->value)>{{ $paper->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">File</label>
            <select class="form-select form-select-sm" name="chapters[{{ $index }}][options][completion]" @disabled($locked)>
                <option value="missing" @selected(($options['completion'] ?? 'missing') === 'missing')>Missing a form</option>
                <option value="complete" @selected(($options['completion'] ?? '') === 'complete')>All forms on file</option>
                <option value="any" @selected(($options['completion'] ?? '') === 'any')>Any closed case</option>
            </select>
        </div>
    </div>
@elseif($chapter['type'] === 'texts')
    <label class="form-label">Which texts</label>
    <select class="form-select form-select-sm" name="chapters[{{ $index }}][options][status]" @disabled($locked)>
        @foreach(['failed' => 'Failed', 'sent' => 'Sent', 'pending' => 'Pending', 'all' => 'All'] as $value => $label)
            <option value="{{ $value }}" @selected(($options['status'] ?? 'failed') === $value)>{{ $label }}</option>
        @endforeach
    </select>
@elseif($chapter['type'] === 'support')
    <div class="row g-2">
        <div class="col-md-4">
            <label class="form-label">Status</label>
            <select class="form-select form-select-sm" name="chapters[{{ $index }}][options][status]" @disabled($locked)>
                @foreach(['new' => 'New', 'reviewed' => 'Reviewed', 'resolved' => 'Resolved', 'all' => 'All'] as $value => $label)
                    <option value="{{ $value }}" @selected(($options['status'] ?? 'new') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Topic</label>
            <select class="form-select form-select-sm" name="chapters[{{ $index }}][options][category]" @disabled($locked)>
                <option value="all">All topics</option>
                @foreach($supportCategories as $key => $category)
                    <option value="{{ $key }}" @selected(($options['category'] ?? 'all') === $key)>{{ $category['label'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">From</label>
            <select class="form-select form-select-sm" name="chapters[{{ $index }}][options][source]" @disabled($locked)>
                <option value="all" @selected(($options['source'] ?? 'all') === 'all')>Public and staff</option>
                <option value="public" @selected(($options['source'] ?? '') === 'public')>Public page</option>
                <option value="agency" @selected(($options['source'] ?? '') === 'agency')>Staff desk</option>
            </select>
        </div>
    </div>
@elseif($chapter['type'] === 'notices')
    <label class="form-label">On the public page</label>
    <select class="form-select form-select-sm" name="chapters[{{ $index }}][options][published]" @disabled($locked)>
        <option value="yes" @selected(($options['published'] ?? 'yes') === 'yes')>Published</option>
        <option value="no" @selected(($options['published'] ?? '') === 'no')>Not published</option>
        <option value="all" @selected(($options['published'] ?? '') === 'all')>Both</option>
    </select>
@elseif($chapter['type'] === 'activity')
    <label class="form-label">Log</label>
    <select class="form-select form-select-sm" name="chapters[{{ $index }}][options][log_name]" @disabled($locked)>
        <option value="all" @selected(($options['log_name'] ?? 'all') === 'all')>All logs</option>
        @foreach($logNames as $name)
            <option value="{{ $name }}" @selected(($options['log_name'] ?? '') === $name)>{{ ucfirst($name) }}</option>
        @endforeach
    </select>
@elseif($chapter['type'] === 'one_case')
    <label class="form-label">Tracking number</label>
    <input type="text" class="form-control form-control-sm" name="chapters[{{ $index }}][options][tracking_number]" value="{{ $options['tracking_number'] ?? '' }}" maxlength="32" placeholder="RAN-…" @disabled($locked)>
@endif
