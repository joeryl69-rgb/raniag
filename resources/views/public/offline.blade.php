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
                You can still file a new report from this phone. It stays on this device and sends when the connection returns. A GPS photo is included when you take one. Without a photo, the report says the location was unavailable. Tracking a report still needs a connection.
            </p>
            <div class="d-grid gap-2">
                <a href="{{ route('public.report.create') }}" class="btn btn-primary btn-lg">
                    <i class="bi bi-megaphone-fill me-1"></i>Report an incident
                </a>
                <button onclick="window.location.reload();" class="btn btn-outline-secondary btn-lg">
                    <i class="bi bi-arrow-clockwise me-1"></i>Try Reconnecting
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
