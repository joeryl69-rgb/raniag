@props(['eyebrow' => 'RANIAG PORTAL', 'title' => 'Welcome!', 'subtitle' => ''])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>RANIAG — {{ config('raniag.organization') }} Incident Reporting System</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}" rel="stylesheet">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0b5ed7">
    <link rel="apple-touch-icon" href="/images/icons/icon-192x192.png">
    <link href="{{ asset('css/auth.css') }}?v={{ @filemtime(public_path('css/auth.css')) }}" rel="stylesheet">
    <script>
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) { window.location.reload(); }
        });
    </script>
</head>
<body>
    <div class="auth-shell">
        <div class="auth-panel">
            <div class="auth-panel__glow"></div>
            <img src="/images/icons/icon-192x192.png" alt="RANIAG" class="auth-panel__mark">
            <h1 class="auth-panel__title">RANIAG</h1>
            <p class="auth-panel__org">{{ config('raniag.organization') }}</p>
            <p class="auth-panel__tagline">"{{ config('raniag.tagline') }}"</p>
            <ul class="auth-panel__points">
                <li><i class="bi bi-shield-check"></i>Secure staff access</li>
                <li><i class="bi bi-geo-alt"></i>Real-time incident coordination</li>
                <li><i class="bi bi-graph-up"></i>Live analytics &amp; reporting</li>
            </ul>
        </div>
        <div class="auth-form-side">
            <div class="auth-form-wrap">
                <a href="{{ route('public.home') }}" class="auth-back-link"><i class="bi bi-arrow-left me-1"></i>Back to Landing Page</a>
                <p class="auth-eyebrow">{{ $eyebrow }}</p>
                <h2 class="auth-title">{{ $title }}</h2>
                @if($subtitle)
                    <p class="auth-subtitle">{{ $subtitle }}</p>
                @endif
                {{ $slot }}
                @isset($footer)
                    <div class="auth-footer">{{ $footer }}</div>
                @endisset
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        // System-wide consistent submit indicator for every auth form
        // (login, forgot-password, reset-password) — pages don't need to
        // wire this up individually.
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.auth-form-side form').forEach(function (form) {
                const btn = form.querySelector('button[type="submit"].auth-submit');
                if (!btn || btn.querySelector('.spinner-border')) return; // already has its own
                const labelText = btn.textContent.trim();
                form.addEventListener('submit', function () {
                    if (btn.disabled) return;
                    btn.disabled = true;
                    btn.dataset.originalHtml = btn.innerHTML;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>' + labelText;
                });
            });
        });
    </script>
    @isset($scripts)
        {{ $scripts }}
    @endisset
</body>
</html>
