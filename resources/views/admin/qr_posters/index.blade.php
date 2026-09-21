@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
        <div>
            <h1 class="h3 mb-1">QR report posters</h1>
            <p class="text-muted mb-0">Create, edit, and delete barangay QR posters. Scanning opens the public report form with that barangay prefilled.</p>
        </div>
        @if ($posters->where('is_active', true)->isNotEmpty())
            <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                <i class="bi bi-printer me-1"></i>Print active posters
            </button>
        @endif
    </div>

    @if ($errors->any())
        <div class="alert alert-danger no-print"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <div class="row g-4">
        <div class="col-lg-4 no-print">
            <div class="card raniag-card shadow-sm border-0">
                <div class="card-header raniag-card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">{{ $editing ? 'Edit poster' : 'Create poster' }}</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ $editing ? route('admin.qr_posters.update', $editing) : route('admin.qr_posters.store') }}">
                        @csrf
                        @if ($editing)
                            @method('PUT')
                        @endif
                        <div class="mb-3">
                            <label class="form-label" for="title">Title</label>
                            <input type="text" name="title" id="title" class="form-control" required maxlength="120"
                                   value="{{ old('title', $editing?->title) }}" placeholder="e.g. Tabba barangay hall">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="barangay">Barangay</label>
                            <select name="barangay" id="barangay" class="form-select" required>
                                <option value="">Select barangay…</option>
                                @foreach ($barangays as $barangay)
                                    <option value="{{ $barangay }}" @selected(old('barangay', $editing?->barangay) === $barangay)>{{ $barangay }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="notes">Notes <span class="text-muted">(optional)</span></label>
                            <input type="text" name="notes" id="notes" class="form-control" maxlength="255"
                                   value="{{ old('notes', $editing?->notes) }}" placeholder="Placement / print notes">
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                                   @checked(old('is_active', $editing?->is_active ?? true))>
                            <label class="form-check-label" for="is_active">Active (include when printing)</label>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary">{{ $editing ? 'Update poster' : 'Create poster' }}</button>
                            @if ($editing)
                                <a href="{{ route('admin.qr_posters.index') }}" class="btn btn-outline-secondary">Cancel</a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card raniag-card shadow-sm border-0 mb-4 no-print">
                <div class="card-header raniag-card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">Saved posters ({{ $posters->count() }})</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Barangay</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($posters as $poster)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $poster->title }}</div>
                                        @if ($poster->notes)<div class="small text-muted">{{ $poster->notes }}</div>@endif
                                    </td>
                                    <td>{{ $poster->barangay }}</td>
                                    <td>
                                        <span class="badge {{ $poster->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                            {{ $poster->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <a href="{{ route('admin.qr_posters.index', ['edit' => $poster->id]) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                        <form method="POST" action="{{ route('admin.qr_posters.destroy', $poster) }}" class="d-inline" onsubmit="return confirm('Delete this QR poster?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-muted">No posters yet. Create one on the left.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="row g-4" id="qr-print-grid">
                @forelse ($posters->where('is_active', true) as $poster)
                    <div class="col-sm-6 col-lg-4">
                        <div class="card raniag-card h-100 text-center p-3 qr-poster-card">
                            <div class="fw-semibold mb-1">{{ $poster->title }}</div>
                            <div class="small text-muted mb-2">Brgy. {{ $poster->barangay }}</div>
                            <img src="{{ $poster->qrImageUrl() }}" alt="QR for {{ $poster->barangay }}" class="img-fluid mx-auto mb-2" width="180" height="180">
                            <div class="small text-muted">Scan to report an incident</div>
                            <div class="small text-break mt-2">{{ $poster->reportUrl() }}</div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="alert alert-light border no-print mb-0">Create an active poster to preview and print QR codes here.</div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
@media print {
    .sidebar, .navbar, .btn, .nav-section-label, .no-print { display: none !important; }
    .qr-poster-card { break-inside: avoid; border: 1px solid #ccc; }
}
</style>
@endpush
@endsection
