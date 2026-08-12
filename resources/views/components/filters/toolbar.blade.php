@props([
    'searchPlaceholder' => 'Search...',
    'action' => null,
])

<form method="GET" action="{{ $action }}" class="row g-2 align-items-end" data-loading-message="Filtering...">
    <div class="col-md-3">
        <label class="form-label small text-muted mb-1">Search</label>
        <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="{{ $searchPlaceholder }}">
    </div>

    {{ $slot ?? '' }}

    <div class="col-md-2">
        <label class="form-label small text-muted mb-1">From</label>
        <input type="date" name="date_from" value="{{ request('date_from') }}" max="{{ now()->toDateString() }}" class="form-control">
    </div>
    <div class="col-md-2">
        <label class="form-label small text-muted mb-1">To</label>
        <input type="date" name="date_to" value="{{ request('date_to') }}" max="{{ now()->toDateString() }}" class="form-control">
    </div>

    <div class="col-md-auto d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-search me-1"></i>Filter
        </button>
        <a href="{{ $action ?? url()->current() }}" class="btn btn-outline-secondary">
            <i class="bi bi-x-circle me-1"></i>Clear
        </a>
    </div>
</form>
