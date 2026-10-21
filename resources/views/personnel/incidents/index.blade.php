<x-app-layout>
    @push('styles')
        <link rel="stylesheet" href="{{ asset('css/incidents.css') }}?v={{ @filemtime(public_path('css/incidents.css')) }}">
    @endpush

    <x-slot name="header">
        {{ __('Assigned Incident Dispatches') }}
    </x-slot>

    <div class="rg-incident-index">
    <section class="rg-command-board" aria-labelledby="rg-queue-heading">
        <header class="rg-command-board-head">
            <div>
                <span class="rg-section-kicker">FIELD OPERATIONS / MY ACTIVE TASKING · DISPATCH DOCKET</span>
                <h1 id="rg-queue-heading">My active dispatches <span>{{ $incidents->total() }}</span></h1>
                <p>New calls surface first. Open a case to acknowledge, log progress or submit a resolution.</p>
            </div>
            <span class="rg-refresh-note"><span aria-hidden="true"></span>Live queue</span>
        </header>
        <x-incidents.queue-controls :action="route('personnel.incidents.index')" :barangays="$barangays" />
        <div class="rg-docket-region" data-live-refresh data-live-refresh-target="#rg-personnel-incident-docket" data-live-refresh-interval="4000">
            <ol class="rg-docket-list" id="rg-personnel-incident-docket" aria-label="Assigned incidents ordered by {{ request('sort', 'reported_at') === 'priority' ? 'priority rank, critical then high, medium and low; report time '.(request('direction', 'desc') === 'asc' ? 'ascending' : 'descending') : (request('sort', 'reported_at') === 'reported_at' ? 'report time ' : request('sort').' ').(request('direction', 'desc') === 'asc' ? 'ascending' : 'descending') }}">
                @forelse ($incidents as $inc)
                    @php $assignment = $inc->currentAssignments->firstWhere('assigned_to', auth()->user()->id) ?? $inc->currentAssignments->first(); @endphp
                    <x-incidents.queue-item :incident="$inc" :detail-url="route('personnel.incidents.show', $inc->id)" action-label="{{ $inc->status->value === 'assigned' ? 'Acknowledge' : 'Open response' }}" :assigned-at="$assignment?->created_at" />
                @empty
                    <li class="rg-docket-empty"><i class="bi bi-inbox" aria-hidden="true"></i><strong>No active dispatches</strong><span>Your personal queue is clear.</span></li>
                @endforelse
            </ol>
        </div>
        <footer class="rg-command-board-foot">
            <span>Showing {{ $incidents->firstItem() ?? 0 }}–{{ $incidents->lastItem() ?? 0 }} of {{ $incidents->total() }} dispatches</span>
            @if ($incidents->hasPages()){!! $incidents->links('pagination::bootstrap-5') !!}@endif
        </footer>
    </section>
    </div>
</x-app-layout>
