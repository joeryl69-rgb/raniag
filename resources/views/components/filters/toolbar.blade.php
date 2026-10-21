@props([
    'searchPlaceholder' => 'Search...',
    'action' => null,
    'searchName' => 'q',
    'dateFromName' => 'date_from',
    'dateToName' => 'date_to',
    'clearUrl' => null,
    'compact' => false,
    'dateFromValue' => null,
    'dateToValue' => null,
    'searchAfterFields' => false,
])

{{--
    Reusable filter toolbar (used across admin/incidents, admin/sms-logs,
    admin/audit-logs, admin/feedback, admin/document_requests, admin/agencies).

    Stays a plain server-rendered GET form — no JSON/AJAX rewrite needed —
    but Alpine.js (see public/js/filter-bar.js) layers on:
      - live active-filter chips, built by reading whatever fields exist in
        the form (built-in search/date range + anything passed into the
        slot), so any page reusing this component gets chips for free
      - one-click chip removal that clears just that field
      - explicit Apply and Clear actions; changing fields never submits

    Fields with a non-empty "default" sentinel value (e.g. a status select
    where "all" or "0" means "no filter") should carry
    data-filter-default="all" so the chip logic treats that value as
    inactive instead of showing a permanent "Status: All" chip.

    If a page renders two toolbars sharing one URL (e.g. agencies +
    personnel tabs), pass distinct search-name/date-from-name/date-to-name
    per instance (e.g. "agency_q" vs "personnel_q") so their query params
    don't collide.
--}}
<div class="rg-filter-bar" x-data="raniagFilterBar()" x-init="init()">
    <form
        method="GET"
        action="{{ $action }}"
        x-ref="form"
        class="{{ $compact ? 'rg-filter-compact-form' : 'row g-2 align-items-end' }}"
        data-auto-submit="false"
        data-loading-message="Filtering..."
        x-on:change="onFieldChange($event)"
    >
    @if ($compact)
        @if ($searchAfterFields)
            <div class="rg-filter-toolbar rg-filter-toolbar-after-fields">
                <div class="rg-filter-toolbar-fields">
                    {{ $slot ?? '' }}
                    <label class="rg-filter-date">
                        <span>From</span>
                        <input type="date" name="{{ $dateFromName }}" value="{{ request($dateFromName, $dateFromValue) }}" max="{{ now()->toDateString() }}" class="form-control">
                    </label>
                    <label class="rg-filter-date">
                        <span>To</span>
                        <input type="date" name="{{ $dateToName }}" value="{{ request($dateToName, $dateToValue) }}" max="{{ now()->toDateString() }}" class="form-control">
                    </label>
                </div>
                <label class="rg-filter-toolbar-search">
                    <span class="visually-hidden">Search</span>
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input
                        type="search"
                        name="{{ $searchName }}"
                        value="{{ request($searchName) }}"
                        class="form-control"
                        placeholder="{{ $searchPlaceholder }}"
                        x-on:input.debounce.250ms="buildChips()"
                    >
                </label>
                <div class="rg-filter-toolbar-actions">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check2 me-1"></i>Apply filters</button>
                    <a href="{{ $clearUrl ?? $action ?? url()->current() }}" class="btn btn-outline-secondary btn-sm" data-loading-link data-loading-message="Clearing filters...">Clear all</a>
                </div>
            </div>
        @else
            <div class="rg-filter-toolbar">
                <label class="rg-filter-toolbar-search">
                    <span class="visually-hidden">Search</span>
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input
                        type="search"
                        name="{{ $searchName }}"
                        value="{{ request($searchName) }}"
                        class="form-control"
                        placeholder="{{ $searchPlaceholder }}"
                        x-on:input.debounce.250ms="buildChips()"
                    >
                </label>
                <details class="rg-filter-menu">
                    <summary aria-label="Toggle filters" aria-expanded="false">
                        <i class="bi bi-funnel" aria-hidden="true"></i>
                        <span>Filters</span>
                        <span class="rg-filter-menu-count" x-show="chips.length" x-cloak x-text="chips.length" aria-label="Active filter count"></span>
                    </summary>
                    <div class="rg-filter-popover">
                        <div class="rg-filter-popover-fields">
                            {{ $slot ?? '' }}
                            <label class="rg-filter-date">
                                <span>From</span>
                                <input type="date" name="{{ $dateFromName }}" value="{{ request($dateFromName, $dateFromValue) }}" max="{{ now()->toDateString() }}" class="form-control">
                            </label>
                            <label class="rg-filter-date">
                                <span>To</span>
                                <input type="date" name="{{ $dateToName }}" value="{{ request($dateToName, $dateToValue) }}" max="{{ now()->toDateString() }}" class="form-control">
                            </label>
                        </div>
                        <div class="rg-filter-popover-actions">
                            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check2 me-1"></i>Apply filters</button>
                            <a href="{{ $clearUrl ?? $action ?? url()->current() }}" class="btn btn-outline-secondary btn-sm" data-loading-link data-loading-message="Clearing filters...">Clear all</a>
                        </div>
                    </div>
                </details>
                <button type="submit" class="rg-filter-search-submit" aria-label="Search and apply filters"><i class="bi bi-arrow-right" aria-hidden="true"></i></button>
            </div>
        @endif
    @else
        <div class="col-md-3">
            <label class="form-label small text-muted mb-1">Search</label>
            <div class="rg-search-input">
                <i class="bi bi-search"></i>
                <input
                    type="search"
                    name="{{ $searchName }}"
                    value="{{ request($searchName) }}"
                    class="form-control"
                    placeholder="{{ $searchPlaceholder }}"
                    x-on:input.debounce.250ms="buildChips()"
                >
            </div>
        </div>

        {{ $slot ?? '' }}

        <div class="col-md-2">
            <label class="form-label small text-muted mb-1">From</label>
            <input type="date" name="{{ $dateFromName }}" value="{{ request($dateFromName, $dateFromValue) }}" max="{{ now()->toDateString() }}" class="form-control">
        </div>
        <div class="col-md-2">
            <label class="form-label small text-muted mb-1">To</label>
            <input type="date" name="{{ $dateToName }}" value="{{ request($dateToName, $dateToValue) }}" max="{{ now()->toDateString() }}" class="form-control">
        </div>

        <div class="col-md-auto d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-funnel me-1"></i>Apply filters
            </button>
            <a href="{{ $clearUrl ?? $action ?? url()->current() }}" class="btn btn-outline-secondary" data-loading-link data-loading-message="Clearing filters...">
                <i class="bi bi-x-circle me-1"></i>Clear
            </a>
        </div>
    @endif
    </form>

    <div class="rg-filter-chips" x-show="chips.length" x-cloak>
        <span class="rg-filter-chip-count"><i class="bi bi-funnel-fill"></i> <span x-text="chips.length"></span> active</span>
        <template x-for="chip in chips" :key="chip.name">
            <span class="rg-filter-chip">
                <span x-text="chip.text"></span>
                <button type="button" x-on:click="removeChip(chip.name)" aria-label="Remove this filter"><i class="bi bi-x"></i></button>
            </span>
        </template>
        <a href="{{ $clearUrl ?? $action ?? url()->current() }}" class="rg-filter-chip-clear" data-loading-link data-loading-message="Clearing filters...">Clear all</a>
    </div>
</div>
