@props([
    'action',
    'admin' => false,
    'incidentTypes' => collect(),
    'statuses' => [],
    'priorities' => [],
])

<div x-data="raniagFilterBar()" x-init="init()">
<form method="GET" action="{{ $action }}" class="rg-queue-controls" aria-label="Refine incident queue" x-ref="form" data-auto-submit="false" x-on:change="onFieldChange($event)">
    <label class="rg-queue-search">
        <span class="visually-hidden">Search incidents</span>
        <i class="bi bi-search" aria-hidden="true"></i>
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Find a case or tracking number" x-on:input.debounce.250ms="buildChips()">
    </label>

    <details class="rg-queue-filter-menu">
        <summary aria-label="Open incident filters" aria-expanded="false"><i class="bi bi-funnel" aria-hidden="true"></i><span>Filters</span><span class="rg-filter-menu-count" x-show="chips.length" x-cloak x-text="chips.length" aria-label="Active filter count"></span></summary>
        <div class="rg-queue-filter-popover">
            <label>
                <span>Status</span>
                <select name="status" @if($admin) data-filter-default="" @endif>
                    @if ($admin)
                        <option value="" @selected(request('status', '') === '')>Not resolved</option>
                    @else
                        <option value="">All statuses</option>
                    @endif
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>Category</span>
                <select name="{{ $admin ? 'incident_type_id' : 'barangay' }}">
                    @if ($admin)
                        <option value="">All categories</option>
                        @foreach ($incidentTypes as $type)
                            <option value="{{ $type->id }}" @selected((string) request('incident_type_id') === (string) $type->id)>{{ $type->name }}</option>
                        @endforeach
                    @else
                        <x-place-filter-options :selected="request('barangay')" all-value="" />
                    @endif
                </select>
            </label>
            @if ($admin)
                <label>
                    <span>Location</span>
                    <select name="jurisdiction">
                        <option value="">All barangays</option>
                        <option value="outside" @selected(request('jurisdiction') === 'outside')>All outside Pamplona</option>
                        <option value="inside" @selected(request('jurisdiction') === 'inside')>All inside Pamplona</option>
                    </select>
                </label>
                <label>
                    <span>Priority</span>
                    <select name="priority">
                        <option value="">All priorities</option>
                        @foreach ($priorities as $value => $label)
                            <option value="{{ $value }}" @selected(request('priority') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            @endif
            <label>
                <span>From</span>
                <input type="date" name="date_from" value="{{ request('date_from') }}">
            </label>
            <label>
                <span>To</span>
                <input type="date" name="date_to" value="{{ request('date_to') }}">
            </label>
            <label>
                <span>Sort by</span>
                <select name="sort" data-filter-default="reported_at" data-filter-ignore>
                    <option value="priority" @selected(request('sort') === 'priority')>Priority</option>
                    <option value="reported_at" @selected(request('sort', 'reported_at') === 'reported_at')>Reported time</option>
                </select>
            </label>
            <label>
                <span>Order</span>
                <select name="direction" data-filter-default="desc" data-filter-ignore>
                    <option value="desc" @selected(request('direction', 'desc') === 'desc')>Newest first</option>
                    <option value="asc" @selected(request('direction') === 'asc')>Oldest first</option>
                </select>
            </label>
            <div class="rg-queue-filter-actions">
                <button class="rg-queue-apply" type="submit">Apply filters</button>
                <a class="rg-queue-clear" href="{{ $action }}">Reset</a>
            </div>
        </div>
    </details>
    <button class="rg-queue-icon-button" type="submit" aria-label="Apply incident search"><i class="bi bi-arrow-right" aria-hidden="true"></i></button>
</form>
<div class="rg-filter-chips" x-show="chips.length" x-cloak>
    <span class="rg-filter-chip-count"><i class="bi bi-funnel-fill"></i> <span x-text="chips.length"></span> active</span>
    <template x-for="chip in chips" :key="chip.name">
        <span class="rg-filter-chip">
            <span x-text="chip.text"></span>
            <button type="button" x-on:click="removeChip(chip.name)" aria-label="Remove this filter"><i class="bi bi-x"></i></button>
        </span>
    </template>
    <a href="{{ $action }}" class="rg-filter-chip-clear" data-loading-link data-loading-message="Clearing filters...">Clear all</a>
</div>
</div>
