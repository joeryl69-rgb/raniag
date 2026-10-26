@extends('layouts.public')

@section('title', 'Offline')

@section('content')
<div class="container my-5 text-center">
    <div class="card raniag-card max-w-lg mx-auto py-5 px-4 shadow-lg border-0" style="max-width: 500px; margin: 0 auto;" data-rg-reveal>
        <div class="card-body">
            <div class="mb-4">
                <span class="display-1 text-secondary"><i class="bi bi-wifi-off"></i></span>
            </div>
            <h1 class="h3 fw-bold text-dark mb-3">You are offline</h1>
            <p class="text-muted mb-4">
                If this phone has saved the report form, you can file a report offline. It stays on this device and sends when the connection returns. Tracking a report still needs a connection.
            </p>
            <div class="d-grid gap-2">
                <a href="{{ route('public.report.create') }}" class="btn btn-primary btn-lg disabled"
                   id="offline-report-link" aria-disabled="true" tabindex="-1">
                    <i class="bi bi-megaphone-fill me-1"></i>Report an incident
                </a>
                <button onclick="window.location.reload();" class="btn btn-outline-secondary btn-lg">
                    <i class="bi bi-arrow-clockwise me-1"></i>Try Reconnecting
                </button>
            </div>
            <p class="small text-muted mt-3 mb-0" id="offline-report-status" role="status" aria-live="polite">
                Checking whether this phone has a saved report form…
            </p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (() => {
        const link = document.getElementById('offline-report-link');
        const status = document.getElementById('offline-report-status');
        if (!link || !status) return;

        const reportUrl = new URL(@json(route('public.report.create', [], false)), window.location.origin);

        async function updateReportLink() {
            const cachedReport = 'caches' in window
                ? await caches.match(new Request(reportUrl.href)).catch(() => null)
                : null;
            let connected = false;
            const controller = new AbortController();
            const timeout = setTimeout(() => controller.abort(), 2500);

            try {
                const response = await fetch('/up', {
                    cache: 'no-store',
                    signal: controller.signal,
                    headers: { Accept: 'text/plain' },
                });
                connected = response.ok;
            } catch (error) {
                connected = false;
            } finally {
                clearTimeout(timeout);
            }

            const available = Boolean(cachedReport) || connected;
            link.classList.toggle('disabled', !available);
            link.setAttribute('aria-disabled', String(!available));
            if (available) {
                link.removeAttribute('tabindex');
                status.textContent = cachedReport
                    ? 'The report form is saved on this phone and can open offline.'
                    : 'You are connected. The report form can be opened now.';
            } else {
                link.setAttribute('tabindex', '-1');
                status.textContent = 'This phone has no saved report form. Reconnect and open the form once to make it available offline.';
            }
        }

        link.addEventListener('click', (event) => {
            if (link.getAttribute('aria-disabled') === 'true') event.preventDefault();
        });
        window.addEventListener('online', updateReportLink);
        window.addEventListener('offline', updateReportLink);
        updateReportLink();
    })();
</script>
@endpush
