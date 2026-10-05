@extends('layouts.public')

@section('title', 'Advisories')

@section('content')
<div class="container">
    <div class="rg-desk-page">
        <header class="rg-desk-head">
            <p class="rg-desk-kicker">Public information</p>
            <h1>Advisories and emergency lines</h1>
            <p>Official updates from {{ config('raniag.organization') }}, the numbers to call, and how many evacuation centers are open.</p>
        </header>

        <div class="rg-desk-stats">
            <article>
                <span>Alert</span>
                <strong>{{ $alertPosture['label'] }}</strong>
            </article>
            <article>
                <span>Published updates</span>
                <strong>{{ $announcements->total() }}</strong>
            </article>
            <article>
                <span>Hotlines</span>
                <strong>{{ $hotlines->count() }}</strong>
            </article>
            <article>
                <span>Open evacuation centers</span>
                <strong>{{ $openCenters }}</strong>
            </article>
        </div>

        @if($alertNote)
            <p class="rg-alert-note">{{ $alertNote }}</p>
        @endif

        <div class="rg-desk-grid">
            <section>
                <form class="rg-desk-search" method="GET" action="{{ route('public.advisories') }}">
                    <label class="visually-hidden" for="advisory-q">Search advisories</label>
                    <input id="advisory-q" type="search" name="q" value="{{ $search }}" placeholder="Search by title or keyword">
                    <button type="submit">Search</button>
                </form>

                <div class="rg-log mt-3">
                    @forelse($announcements as $item)
                        <article class="rg-log-item rg-announce-card">
                            <time datetime="{{ optional($item->published_at)->toDateString() }}">{{ optional($item->published_at)->format('M d, Y') }}</time>
                            <div>
                                <div class="rg-log-meta">
                                    <i class="bi {{ $item->icon ?? 'bi-megaphone-fill' }}"></i>
                                    @if($item->badge)<span>{{ $item->badge }}</span>@endif
                                </div>
                                <h2>{{ $item->title }}</h2>
                                <p>{{ $item->body }}</p>
                            </div>
                        </article>
                    @empty
                        <div class="rg-support-card text-center text-muted py-4">
                            {{ $search !== '' ? 'No advisories match that search.' : 'No public advisories yet.' }}
                        </div>
                    @endforelse
                </div>

                @if($announcements->hasPages())
                    <div class="mt-3">{{ $announcements->links('pagination::bootstrap-5') }}</div>
                @endif
            </section>

            <aside class="rg-hotline-card">
                <h2>Emergency lines</h2>
                @forelse($hotlines as $hotline)
                    <a class="rg-hotline-row" href="{{ $hotline->dialHref() }}">
                        <span>
                            <strong>{{ $hotline->name }}</strong>
                            @if($hotline->detail)<small>{{ $hotline->detail }}</small>@endif
                        </span>
                        <em>{{ $hotline->number }}</em>
                    </a>
                @empty
                    <p class="rg-hotline-empty">Hotlines will appear here once {{ config('raniag.organization') }} publishes them.</p>
                @endforelse
                <a class="rg-hotline-map" href="{{ route('public.hazard.map') }}">Open evacuation centers on the live map</a>
            </aside>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.rg-desk-page { display: grid; gap: 16px; }
.rg-desk-head h1 { margin: 0 0 6px; font-size: clamp(1.6rem, 2.4vw, 2.1rem); font-weight: 800; letter-spacing: -.02em; }
.rg-desk-head p:last-child { margin: 0; max-width: 46rem; color: var(--rg-muted); }
.rg-desk-kicker { margin: 0 0 4px; font-size: .72rem; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; color: var(--rg-brand); }
.rg-desk-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
.rg-desk-stats article { background: #fff; border: 1px solid var(--rg-line); border-radius: 14px; padding: 14px 16px; }
.rg-desk-stats span { display: block; font-size: .72rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--rg-muted); }
.rg-desk-stats strong { display: block; margin-top: 4px; font-size: 1.35rem; }
.rg-alert-note { margin: 0; padding: 12px 14px; border-radius: 12px; background: #fff; border: 1px solid var(--rg-line); }
.rg-desk-grid { display: grid; grid-template-columns: minmax(0, 1.4fr) minmax(260px, .7fr); gap: 16px; align-items: start; }
.rg-desk-search { display: flex; gap: 8px; }
.rg-desk-search input { flex: 1; border: 1px solid var(--rg-line); border-radius: 12px; padding: .7rem .9rem; background: #fff; }
.rg-desk-search button { border: 0; border-radius: 12px; background: var(--rg-brand); color: #fff; font-weight: 700; padding: 0 16px; }
.rg-log-item h2 { margin: 2px 0; font-size: 1rem; font-weight: 800; }
@media (max-width: 991.98px) {
    .rg-desk-stats, .rg-desk-grid { grid-template-columns: 1fr; }
}
</style>
@endpush
