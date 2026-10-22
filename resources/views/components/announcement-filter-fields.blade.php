@props(['includeStatus' => false])

<div class="col-md-2">
    <label class="form-label small text-muted mb-1">Type</label>
    <select name="type" class="form-select" data-filter-default="all">
        <option value="all" @selected(request('type', 'all') === 'all')>All types</option>
        @foreach(\App\Models\Announcement::filterableBadges(! $includeStatus) as $badge)
            <option value="{{ $badge }}" @selected(request('type') === $badge)>{{ \App\Models\Announcement::typeLabel($badge) }}</option>
        @endforeach
    </select>
</div>
@if($includeStatus)
    <div class="col-md-2">
        <label class="form-label small text-muted mb-1">Status</label>
        <select name="status" class="form-select" data-filter-default="all">
            <option value="all" @selected(request('status', 'all') === 'all')>All statuses</option>
            @foreach (\App\Models\Announcement::filterablePublicationStatuses() as $value => $label)
                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
@endif
