<x-app-layout>
    <x-slot name="header">{{ __('Situation report') }}</x-slot>

<main class="quick-report-page">
    <div class="quick-page-head">
        <div>
            <a href="{{ route('admin.reports.index') }}" class="quick-back"><i class="bi bi-arrow-left"></i> Report home</a>
            <p class="quick-report-kicker">Automatic report · MDRRMO Pamplona</p>
            <h1>Situation report</h1>
            <p>Incident patterns, the barangay hotspot map, case details, response timing, and current shelter numbers—assembled automatically from RANIAG.</p>
        </div>
        <form method="POST" action="{{ route('admin.reports.quick.file') }}">
            @csrf
            <input type="hidden" name="period" value="{{ $periodKey }}">
            <button class="btn btn-primary btn-lg" type="submit"><i class="bi bi-file-earmark-check me-2"></i>File report and save PDF</button>
            <small class="quick-file-note">Saves a locked PDF of this situation report.</small>
        </form>
    </div>

    <div class="quick-period-control">
        <div><strong>Dates covered</strong><span>Choose a time window; RANIAG fills in the report for you.</span></div>
        <div class="btn-group" role="group" aria-label="Report period">
            @foreach(['week' => 'This week', 'month' => 'This month', 'year' => 'This year'] as $key => $label)
                <a href="{{ route('admin.reports.quick', ['period' => $key]) }}" @class(['btn', 'btn-primary' => $periodKey === $key, 'btn-outline-primary' => $periodKey !== $key])>{{ $label }}</a>
            @endforeach
        </div>
    </div>

    <article class="quick-report-document">
        <header class="quick-document-cover">
            <div class="quick-letterhead">
                <span class="quick-mark"><i class="bi bi-shield-fill-check"></i></span>
                <div><strong>MDRRMO PAMPLONA</strong><small>Operations and incident coordination</small></div>
                <span class="quick-document-type">SITUATION REPORT</span>
            </div>
            <h2>Operations situation — {{ $quickReport['period_label'] }}</h2>
            <p>{{ \Carbon\Carbon::parse($quickReport['from'])->format('F j, Y') }} – {{ \Carbon\Carbon::parse($quickReport['to'])->format('F j, Y') }} <span>·</span> Generated {{ $quickReport['generated_at'] }}</p>
        </header>
        @include('admin.reports.partials._quick_report_content', ['quickReport' => $quickReport])
    </article>

    @if($records->isNotEmpty())
        <section class="quick-recent">
            <h2>Recently filed situation reports</h2>
            <div>
                @foreach($records as $record)
                    <a href="{{ route('admin.reports.records.show', $record) }}">
                        <span><i class="bi bi-file-earmark-pdf"></i>{{ $record->title }}</span>
                        <small>{{ $record->filed_at?->format('M j, Y g:i a') }} <i class="bi bi-arrow-up-right"></i></small>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</main>

@push('styles')
<link rel="stylesheet" href="{{ asset('css/quick-reports.css') }}?v={{ @filemtime(public_path('css/quick-reports.css')) }}">
@endpush
</x-app-layout>
