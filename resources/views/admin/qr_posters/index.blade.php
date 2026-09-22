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
                    <div class="col-lg-6">
                        <div class="raniag-poster">
                            <div class="raniag-poster-header">
                                <img src="{{ asset('images/letterhead/mdrrmo-logo.png') }}" alt="MDRRMO Pamplona" class="raniag-poster-logo">
                                <div class="raniag-poster-heading">
                                    <div class="raniag-poster-eyebrow">Republic of the Philippines &middot; Province of Cagayan &middot; Municipality of Pamplona</div>
                                    <div class="raniag-poster-title">MDRRMO Pamplona &mdash; RANIAG Incident Reporting</div>
                                </div>
                                <img src="{{ asset('images/letterhead/bayan-logo.png') }}" alt="Bayan ng Pamplona" class="raniag-poster-logo">
                            </div>
                            <div class="raniag-poster-body">
                                <div class="raniag-poster-barangay">Brgy. {{ $poster->barangay }}</div>
                                <div class="raniag-poster-qr-wrap">
                                    <div class="raniag-poster-qr" data-qr-target data-qr-url="{{ $poster->reportUrl() }}" data-qr-title="{{ $poster->title }}">
                                        <div class="raniag-poster-qr-loading">Generating QR&hellip;</div>
                                    </div>
                                </div>
                                <div class="raniag-poster-scan">SCAN TO REPORT AN INCIDENT</div>
                                @if ($poster->notes)
                                    <div class="raniag-poster-notes">{{ $poster->notes }}</div>
                                @endif
                                <div class="raniag-poster-url">{{ $poster->reportUrl() }}</div>
                            </div>
                            <div class="raniag-poster-actions no-print">
                                <button type="button" class="btn btn-sm btn-outline-primary raniag-poster-download"
                                        data-filename="qr-poster-{{ Str::slug($poster->barangay) }}.png">
                                    <i class="bi bi-download me-1"></i>Download PNG
                                </button>
                            </div>
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
.raniag-poster {
    border: 1px solid var(--rg-line, #dee2e6);
    border-radius: 14px;
    overflow: hidden;
    background: #fff;
    display: flex;
    flex-direction: column;
    height: 100%;
}
.raniag-poster-header {
    background: #1a365d;
    color: #fff;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
}
.raniag-poster-logo { width: 44px; height: 44px; object-fit: contain; background: #fff; border-radius: 50%; padding: 3px; flex: 0 0 auto; }
.raniag-poster-heading { flex: 1 1 auto; text-align: center; }
.raniag-poster-eyebrow { font-size: .65rem; text-transform: uppercase; letter-spacing: .04em; opacity: .85; }
.raniag-poster-title { font-size: .95rem; font-weight: 700; margin-top: 2px; }
.raniag-poster-body { padding: 28px 20px; text-align: center; flex: 1 1 auto; }
.raniag-poster-barangay { font-size: 1.4rem; font-weight: 800; color: #1a365d; margin-bottom: 14px; }
.raniag-poster-qr-wrap { display: flex; justify-content: center; margin-bottom: 14px; }
.raniag-poster-qr { width: 240px; height: 240px; display: flex; align-items: center; justify-content: center; }
.raniag-poster-qr-loading { font-size: .75rem; color: #999; }
.raniag-poster-qr canvas, .raniag-poster-qr img { border-radius: 8px; }
.raniag-poster-scan { font-weight: 700; letter-spacing: .05em; font-size: .85rem; color: #1a365d; }
.raniag-poster-notes { font-size: .8rem; color: #666; margin-top: 8px; }
.raniag-poster-url { font-size: .7rem; color: #999; word-break: break-all; margin-top: 10px; }
.raniag-poster-actions { padding: 0 20px 18px; text-align: center; }

@media print {
    .sidebar, .navbar, .btn, .nav-section-label, .no-print { display: none !important; }
    #qr-print-grid { display: block !important; }
    #qr-print-grid > div { width: 100%; page-break-after: always; break-after: page; display: flex; align-items: center; justify-content: center; min-height: 90vh; }
    #qr-print-grid > div:last-child { page-break-after: auto; break-after: auto; }
    .raniag-poster { border: none; width: 100%; max-width: 640px; }
    .raniag-poster-body { padding: 60px 40px; }
    .raniag-poster-qr { width: 320px; height: 320px; margin: 0 auto; }
    .raniag-poster-barangay { font-size: 2rem; }
}
</style>
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js" integrity="sha512-CNgIRecGo7nphbeZ04Sc13ka07paqdeTu0WR1IM4kNcpmBAUSHSQX0FslNhTDadL4O5SAGapGt4FodqL8My0mA==" crossorigin="anonymous"></script>
<script src="{{ asset('js/qr-poster.js') }}"></script>
@endpush
@endsection
