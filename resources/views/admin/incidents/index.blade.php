<x-app-layout>
    @push('styles')
        <link rel="stylesheet" href="{{ asset('css/incidents.css') }}?v={{ @filemtime(public_path('css/incidents.css')) }}">
    @endpush

    <x-slot name="header">
        {{ __('Incidents') }}
    </x-slot>

    <div class="rg-incident-index">
    <x-kpi-strip :items="[
        ['label' => 'All incidents', 'value' => $incidentKpis['total'], 'icon' => 'bi-clipboard2-pulse', 'tone' => 'primary', 'sub' => 'Every recorded case'],
        ['label' => 'Awaiting review', 'value' => $incidentKpis['submitted'], 'icon' => 'bi-inbox', 'tone' => 'warning', 'sub' => 'New public reports', 'href' => route('admin.incidents.index', ['status' => 'submitted'])],
        ['label' => 'In progress', 'value' => $incidentKpis['in_progress'], 'icon' => 'bi-activity', 'tone' => 'danger', 'sub' => 'Active responses', 'href' => route('admin.incidents.index', ['status' => 'in_progress'])],
        ['label' => 'Resolved or closed', 'value' => $incidentKpis['resolved'], 'icon' => 'bi-check-circle', 'tone' => 'success', 'sub' => 'Completed cases'],
    ]" />

    <section class="rg-command-board" aria-labelledby="rg-queue-heading" data-view-mode-root="admin-incident-queue" data-view-mode-user="{{ auth()->id() }}">
        <header class="rg-command-board-head">
            <div>
                <span class="rg-section-kicker">INCIDENT MANAGEMENT</span>
                <h1 id="rg-queue-heading">Incident queue <span>{{ $incidents->total() }}</span></h1>
                <p>Review reports, check the scene details, and route each response.</p>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <x-view-mode-toggle register="admin-incident-queue" label="Incident queue" />
                <span class="rg-refresh-note"><span aria-hidden="true"></span>Live queue</span>
            </div>
        </header>
        <x-incidents.queue-controls :action="route('admin.incidents.index')" :admin="true" :incident-types="$incidentTypes" :statuses="$queueStatuses" :priorities="$queuePriorities" />
        <div class="rg-table-wrap" data-live-refresh data-live-refresh-target="#rg-admin-incident-rows" data-live-refresh-interval="4000">
            <table class="rg-incidents-table">
                <thead>
                    @php
                        $sortLink = function (string $column, string $label) {
                            $direction = request('sort', 'reported_at') === $column && request('direction', 'desc') === 'asc' ? 'desc' : 'asc';
                            $query = array_merge(request()->query(), ['sort' => $column, 'direction' => $direction, 'page' => null]);
                            $icon = request('sort', 'reported_at') === $column
                                ? (request('direction', 'desc') === 'asc' ? 'bi-sort-up' : 'bi-sort-down')
                                : 'bi-arrow-down-up';
                            return '<a href="'.e(route('admin.incidents.index', $query)).'" aria-label="Sort by '.e($label).'"><span>'.e($label).'</span><i class="bi '.$icon.'" aria-hidden="true"></i></a>';
                        };
                    @endphp
                    <tr>
                        <th>Incident / tracking</th>
                        <th>Response status</th>
                        <th>{!! $sortLink('priority', 'Priority') !!}</th>
                        <th>Location</th>
                        <th>{!! $sortLink('reported_at', 'Reported') !!}</th>
                        <th><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody id="rg-admin-incident-rows">
                    @forelse ($incidents as $inc)
                        <tr>
                            <td>
                                <a class="rg-table-incident" href="{{ route('admin.incidents.show', $inc->id) }}">
                                    <span class="rg-table-type" style="--case-type-color: {{ $inc->incidentType?->color ?? '#64748b' }}">{{ $inc->incidentType?->name ?? 'Incident' }}</span>
                                    <strong>{{ $inc->title ?: ($inc->incidentType?->name ?? 'Incident').' report' }}</strong>
                                    <small>{{ $inc->tracking_number }}</small>
                                </a>
                            </td>
                            <td><x-public.status-badge :status="$inc->status" /></td>
                            <td><x-priority-badge :priority="$inc->priority" /></td>
                            <td>
                                <span class="rg-table-location"><i class="bi bi-geo-alt" aria-hidden="true"></i>{{ $inc->barangay ?: 'Not provided' }}</span>
                                @if (($inc->meta['within_jurisdiction'] ?? null) === false)
                                    <small class="rg-table-outside">Outside Pamplona</small>
                                @endif
                            </td>
                            <td><time class="rg-table-reported" datetime="{{ $inc->reported_at?->toIso8601String() }}">{{ $inc->reported_at?->format('M d, Y') }}<small>{{ $inc->reported_at?->format('h:i A') }}</small></time></td>
                            <td class="text-end"><a class="rg-table-open" href="{{ route('admin.incidents.show', $inc->id) }}" aria-label="Open case {{ $inc->tracking_number }}"><i class="bi bi-arrow-up-right" aria-hidden="true"></i></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="rg-table-empty"><i class="bi bi-inbox" aria-hidden="true"></i><strong>No incidents found</strong><span>Adjust or clear the filters to see more reports.</span></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <footer class="rg-command-board-foot">
            <span>Showing {{ $incidents->firstItem() ?? 0 }}–{{ $incidents->lastItem() ?? 0 }} of {{ $incidents->total() }} reports</span>
            @if ($incidents->hasPages()){!! $incidents->links('pagination::bootstrap-5') !!}@endif
        </footer>
    </section>
    </div>
</x-app-layout>
