@extends('layouts.public')

@section('title', 'Offline')

@section('content')
<div class="container my-5 text-center">
    <div class="card raniag-card max-w-lg mx-auto py-5 px-4 shadow-lg border-0" style="max-width: 500px; margin: 0 auto;">
        <div class="card-body">
            <div class="mb-4">
                <span class="display-1 text-secondary"><i class="bi bi-wifi-off"></i></span>
            </div>
            <h1 class="h3 fw-bold text-dark mb-3">You are offline</h1>
            <p class="text-muted mb-4">
                It looks like you are not connected to the internet. 
                RANIAG requires an active internet connection to submit new reports or track updates.
            </p>
            <div class="d-grid gap-2">
                <button onclick="window.location.reload();" class="btn btn-primary btn-lg">
                    <i class="bi bi-arrow-clockwise me-1"></i>Try Reconnecting
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
