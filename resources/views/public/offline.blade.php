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
                <a href="{{ route('public.report.create') }}" class="btn btn-primary btn-lg"
                   id="offline-report-link">
                    <i class="bi bi-megaphone-fill me-1"></i>Report an incident
                </a>
                <button onclick="window.location.reload();" class="btn btn-outline-secondary btn-lg">
                    <i class="bi bi-arrow-clockwise me-1"></i>Try Reconnecting
                </button>
            </div>
            <p class="small text-muted mt-3 mb-0" id="offline-report-status" role="status" aria-live="polite">
                Opening the report form…
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
        let linkCheck = 0;

        function setAvailable(available, message) {
            link.classList.toggle('disabled', !available);
            link.setAttribute('aria-disabled', String(!available));
            if (available) {
                link.removeAttribute('tabindex');
            } else {
                link.setAttribute('tabindex', '-1');
            }
            status.textContent = message;
        }

        async function hasCachedReport() {
            if (!('caches' in window)) return false;

            try {
                return Boolean(await caches.match(reportUrl.href, { ignoreSearch: true }));
            } catch (error) {
                console.warn('[raniag-offline] could not check the saved report form', error);
                return false;
            }
        }

        function prepareReportForOffline() {
            if (!navigator.onLine || !('serviceWorker' in navigator)) return;

            navigator.serviceWorker.ready.then((registration) => {
                registration.active?.postMessage({ type: 'raniag-cache-report' });
            }).catch((error) => {
                console.warn('[raniag-offline] could not prepare the report form for offline use', error);
            });
        }

        async function updateReportLink() {
            const currentCheck = ++linkCheck;
            if (navigator.onLine !== false) {
                setAvailable(true, 'You are connected. The report form is ready to open and will be saved for offline use.');
                prepareReportForOffline();
                return;
            }

            setAvailable(false, 'Checking this phone for a saved report form…');
            const available = await hasCachedReport();
            if (currentCheck !== linkCheck || navigator.onLine !== false) return;
            if (available) {
                setAvailable(true, 'The saved report form is ready to open offline.');
            } else {
                setAvailable(false, 'This phone has no saved report form yet. Reconnect and open the report form once to save it for offline use.');
            }
        }

        link.addEventListener('click', (event) => {
            if (link.getAttribute('aria-disabled') === 'true') event.preventDefault();
        });
        window.addEventListener('online', updateReportLink);
        window.addEventListener('offline', updateReportLink);
        navigator.serviceWorker?.addEventListener('controllerchange', updateReportLink);
        updateReportLink();
    })();
</script>
@endpush
