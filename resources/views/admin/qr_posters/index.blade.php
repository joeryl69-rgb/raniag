<x-app-layout>
    <x-slot name="header">{{ __('QR Posters') }}</x-slot>

@php
    $storeUrl = route('admin.qr_posters.store');
    $updateTemplate = route('admin.qr_posters.update', ['qrPoster' => '__ID__']);
@endphp

<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2 no-print">
    <p class="small text-muted mb-0">One poster per barangay. Scanning it opens the public report form with that barangay already filled in.</p>
    <div class="d-flex gap-2">
        @if ($posters->where('is_active', true)->isNotEmpty())
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                <i class="bi bi-printer me-1"></i>Print active posters
            </button>
        @endif
        <button type="button" class="btn btn-primary btn-sm" id="poster-create-btn" data-bs-toggle="modal" data-bs-target="#posterModal">
            <i class="bi bi-plus-lg me-1"></i>New poster
        </button>
    </div>
</div>

<div class="card border-0 shadow-sm no-print" id="tab-manage">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Title</th>
                    <th>Barangay</th>
                    <th>Notes</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($posters as $poster)
                    <tr>
                        <td class="fw-semibold">{{ $poster->title }}</td>
                        <td>{{ $poster->barangay }}</td>
                        <td class="text-muted small">{{ $poster->notes ?: '—' }}</td>
                        <td>
                            <span class="badge {{ $poster->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                {{ $poster->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="text-end text-nowrap">
                            <button type="button" class="btn btn-sm btn-outline-primary js-edit-poster"
                                data-id="{{ $poster->id }}"
                                data-title="{{ $poster->title }}"
                                data-barangay="{{ $poster->barangay }}"
                                data-notes="{{ $poster->notes }}"
                                data-active="{{ $poster->is_active ? '1' : '0' }}">Edit</button>
                            <form method="POST" action="{{ route('admin.qr_posters.destroy', $poster) }}" class="d-inline" onsubmit="return confirm('Delete the poster for {{ $poster->barangay }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No posters yet. Add one for a barangay hall or waiting area.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4" id="tab-previews">
    <div class="d-flex justify-content-between align-items-end mb-3 no-print">
        <div>
            <h2 class="h6 fw-bold mb-1">Print preview</h2>
            <p class="small text-muted mb-0">Active posters only. Print sends one poster per page.</p>
        </div>
    </div>
    <div class="row g-4" id="qr-print-grid">
        @forelse ($posters->where('is_active', true) as $poster)
            <div class="col-lg-6">
                <div class="raniag-poster" data-barangay="Brgy. {{ $poster->barangay }}" data-notes="{{ $poster->notes }}">
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
                    </div>
                    <div class="raniag-poster-actions no-print">
                        <button type="button" class="btn btn-sm btn-outline-primary raniag-poster-download"
                                data-filename="qr-poster-{{ \Illuminate\Support\Str::slug($poster->barangay) }}.png">
                            <i class="bi bi-download me-1"></i>Download PNG
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 no-print">
                <div class="alert alert-light border mb-0">Create an active poster to preview it here.</div>
            </div>
        @endforelse
    </div>
</div>

<div class="modal fade" id="posterModal" tabindex="-1" aria-labelledby="posterModalTitle">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" id="poster-form" action="{{ $editing ? route('admin.qr_posters.update', $editing) : $storeUrl }}">
            @csrf
            @if ($editing)
                @method('PUT')
            @endif
            <input type="hidden" name="poster_id" id="poster_id" value="{{ old('poster_id', $editing?->id) }}">
            <div class="modal-header">
                <h6 class="modal-title fw-bold" id="posterModalTitle">{{ $editing ? 'Edit poster' : 'New poster' }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning py-2 {{ $editing ? '' : 'd-none' }}" id="poster-edit-banner">
                    You are editing <strong id="poster-edit-name">{{ $editing?->title }}</strong>. Saving updates this poster. It does not create another one.
                </div>
                <div class="mb-3">
                    <label class="form-label" for="title">Title</label>
                    <input type="text" name="title" id="title" class="form-control" required maxlength="120"
                           value="{{ old('title', $editing?->title) }}" placeholder="e.g. Tabba barangay hall">
                    <div class="form-text">Staff label only. The printed poster shows the barangay, plus the note below if you add one.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="barangay">Barangay</label>
                    <select name="barangay" id="barangay" class="form-select" required>
                        <option value="">Select barangay…</option>
                        @foreach ($barangays as $barangay)
                            <option value="{{ $barangay }}" @selected(old('barangay', $editing?->barangay) === $barangay)>{{ $barangay }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">Barangays that already have a poster are hidden.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="notes">Placement note <span class="text-muted">(optional)</span></label>
                    <input type="text" name="notes" id="notes" class="form-control" maxlength="255"
                           value="{{ old('notes', $editing?->notes) }}" placeholder="Printed under the QR code">
                </div>
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                           @checked(old('is_active', $editing?->is_active ?? true))>
                    <label class="form-check-label" for="is_active">Active — include when printing</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary" id="poster-submit">{{ $editing ? 'Save changes' : 'Create poster' }}</button>
            </div>
        </form>
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
.raniag-poster-actions { padding: 0 20px 18px; text-align: center; }
@media print {
    @page { size: A4 portrait; margin: 12mm; }
    .sidebar, #sidebar-wrapper, .navbar, .topbar, .btn, .nav-section-label, .no-print, .mobile-dock, #global-loading-overlay, .modal {
        display: none !important;
    }
    #tab-manage { display: none !important; }
    #tab-previews { display: block !important; }
    body { margin: 0 !important; padding: 0 !important; background: #fff !important; }
    #wrapper, #page-content-wrapper, .container-fluid, #qr-print-grid {
        margin: 0 !important; padding: 0 !important; border: none !important; box-shadow: none !important; background: #fff !important; width: 100% !important; max-width: none !important;
    }
    #qr-print-grid { display: block !important; }
    #qr-print-grid > div {
        width: 100% !important; max-width: none !important; display: flex !important; align-items: center; justify-content: center;
        page-break-after: always; break-after: page; page-break-inside: avoid; break-inside: avoid; padding: 0 !important; margin: 0 !important;
    }
    #qr-print-grid > div:last-child { page-break-after: auto; break-after: auto; }
    .raniag-poster { border: 1px solid #1a365d; border-radius: 0; width: 100%; max-width: 170mm; height: auto !important; page-break-inside: avoid; }
    .raniag-poster-body { padding: 28px 24px; }
    .raniag-poster-qr { width: 280px; height: 280px; margin: 0 auto; }
    .raniag-poster-qr canvas, .raniag-poster-qr img { width: 280px !important; height: 280px !important; }
    .raniag-poster-barangay { font-size: 1.75rem; }
}
</style>
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js" integrity="sha512-CNgIRecGo7nphbeZ04Sc13ka07paqdeTu0WR1IM4kNcpmBAUSHSQX0FslNhTDadL4O5SAGapGt4FodqL8My0mA==" crossorigin="anonymous"></script>
<script src="{{ asset('js/qr-poster.js') }}?v={{ @filemtime(public_path('js/qr-poster.js')) }}"></script>
<script>
(function () {
    const form = document.getElementById('poster-form');
    const modalEl = document.getElementById('posterModal');
    const storeUrl = @json($storeUrl);
    const updateTemplate = @json($updateTemplate);
    const allBarangays = @json(array_values($allBarangays));
    const taken = @json($posters->pluck('barangay')->values());
    const title = document.getElementById('posterModalTitle');
    const banner = document.getElementById('poster-edit-banner');
    const bannerName = document.getElementById('poster-edit-name');
    const submit = document.getElementById('poster-submit');
    const barangay = document.getElementById('barangay');

    function fillBarangays(selected, keep) {
        const blocked = new Set(taken.filter((name) => name !== keep));
        barangay.innerHTML = '<option value="">Select barangay…</option>';
        allBarangays.forEach((name) => {
            if (blocked.has(name)) return;
            const opt = document.createElement('option');
            opt.value = name;
            opt.textContent = name;
            if (name === selected) opt.selected = true;
            barangay.appendChild(opt);
        });
    }

    function setMethod(editing) {
        let method = form.querySelector('input[name="_method"]');
        if (editing) {
            if (!method) {
                method = document.createElement('input');
                method.type = 'hidden';
                method.name = '_method';
                form.appendChild(method);
            }
            method.value = 'PUT';
        } else if (method) {
            method.remove();
        }
    }

    function openEdit(data) {
        form.action = updateTemplate.replace('__ID__', data.id);
        setMethod(true);
        document.getElementById('poster_id').value = data.id;
        document.getElementById('title').value = data.title || '';
        document.getElementById('notes').value = data.notes || '';
        document.getElementById('is_active').checked = data.active === '1' || data.active === true;
        fillBarangays(data.barangay, data.barangay);
        title.textContent = 'Edit poster';
        banner.classList.remove('d-none');
        bannerName.textContent = data.title || data.barangay;
        submit.textContent = 'Save changes';
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    function openCreate() {
        form.action = storeUrl;
        setMethod(false);
        form.reset();
        document.getElementById('poster_id').value = '';
        document.getElementById('is_active').checked = true;
        fillBarangays('', null);
        title.textContent = 'New poster';
        banner.classList.add('d-none');
        submit.textContent = 'Create poster';
    }

    document.querySelectorAll('.js-edit-poster').forEach((btn) => {
        btn.addEventListener('click', () => openEdit(btn.dataset));
    });
    document.getElementById('poster-create-btn')?.addEventListener('click', openCreate);

    @if ($editing || ($errors->any() && old('poster_id')))
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    @elseif ($errors->any())
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    @endif
})();
</script>
@endpush
</x-app-layout>
