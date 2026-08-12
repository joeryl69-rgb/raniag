<x-app-layout>
    <x-slot name="header">
        {{ __('Archived Reports') }}
    </x-slot>

    <div class="d-flex mb-4">
        <a href="{{ route('agency.dashboard') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card raniag-card shadow-sm border-0 mb-4">
        <div class="card-header raniag-card-header py-3 border-0">
            <h5 class="mb-1 fw-bold"><i class="bi bi-archive text-success me-2"></i>Archived Reports Repository</h5>
            <p class="text-muted small mb-0">Resolved and closed incidents your agency was assigned to. Download the full case file as a password-verified ZIP.</p>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('agency.archived_reports.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small mb-1">Search Tracking #</label>
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="e.g. RAN-1234">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">From</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" max="{{ now()->format('Y-m-d') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">To</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" max="{{ now()->format('Y-m-d') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Barangay</label>
                    <select name="barangay" class="form-select form-select-sm">
                        <option value="all">All Barangays</option>
                        @foreach($barangays as $b)
                            <option value="{{ $b }}" @selected(request('barangay') === $b)>{{ $b }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="all" @selected(request('status', 'all') === 'all')>All</option>
                        <option value="resolved" @selected(request('status') === 'resolved')>Resolved</option>
                        <option value="closed" @selected(request('status') === 'closed')>Closed</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Sort By</label>
                    <select name="sort" class="form-select form-select-sm">
                        <option value="reported_at" @selected(request('sort', 'reported_at') === 'reported_at')>Date Reported</option>
                        <option value="tracking_number" @selected(request('sort') === 'tracking_number')>Tracking #</option>
                        <option value="barangay" @selected(request('sort') === 'barangay')>Barangay</option>
                    </select>
                </div>
                <div class="col-12 d-flex gap-2 mt-2">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i>Apply Filters</button>
                    <a href="{{ route('agency.archived_reports.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card raniag-card shadow-sm border-0">
        <div class="card-header raniag-card-header py-2 border-0 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <span class="small text-muted" id="selectedCounter">0 selected</span>
            <button type="button" class="btn btn-sm btn-success" id="btnBulkDownload" disabled>
                <i class="bi bi-file-earmark-zip me-1"></i>Download Selected (ZIP)
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 130px;">
                            <div class="form-check mb-0 d-flex align-items-center gap-1">
                                <input type="checkbox" class="form-check-input" id="selectAllCheckbox" @if($reports->isEmpty()) disabled @endif>
                                <label for="selectAllCheckbox" class="form-check-label small fw-semibold mb-0" style="cursor:pointer;">Select All</label>
                            </div>
                        </th>
                        <th>Tracking #</th>
                        <th class="d-none d-md-table-cell">Type</th>
                        <th class="d-none d-md-table-cell">Barangay</th>
                        <th>Status</th>
                        <th class="d-none d-lg-table-cell">Files</th>
                        <th class="d-none d-lg-table-cell">Reported At</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reports as $incident)
                        <tr>
                            <td><input type="checkbox" class="form-check-input row-checkbox" value="{{ $incident->id }}"></td>
                            <td class="fw-semibold">{{ $incident->tracking_number }}</td>
                            <td class="d-none d-md-table-cell">{{ $incident->incidentType?->name }}</td>
                            <td class="d-none d-md-table-cell">{{ $incident->barangay }}</td>
                            <td>
                                <span class="badge {{ $incident->status->value === 'resolved' ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $incident->status->label() }}
                                </span>
                            </td>
                            <td class="d-none d-lg-table-cell">
                                <i class="bi bi-file-earmark-text text-muted"></i> {{ $incident->incident_documents_count }}
                                <i class="bi bi-image text-muted ms-2"></i> {{ $incident->evidence_count }}
                            </td>
                            <td class="d-none d-lg-table-cell">{{ $incident->reported_at?->format('M d, Y H:i') }}</td>
                            <td class="text-end">
                                <a href="{{ route('agency.archived_reports.show', $incident->id) }}" class="btn btn-sm btn-outline-primary" data-loading-link data-loading-message="Loading report...">
                                    <i class="bi bi-eye me-1"></i>View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                No archived reports found for the selected filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($reports->hasPages())
            <div class="card-footer bg-white py-3">
                {{ $reports->links() }}
            </div>
        @endif
    </div>

    {{-- Bulk download password confirmation modal --}}
    <div class="modal fade" id="bulkPasswordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title"><i class="bi bi-shield-lock me-1"></i>Confirm Bulk Download</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">For security, please re-enter your account password to download <span id="bulkPasswordCount">0</span> archive(s) as a single ZIP.</p>
                    <input type="password" class="form-control" id="bulk-archive-password-input" placeholder="Account password">
                    <div class="text-danger small mt-1 d-none" id="bulk-archive-password-error"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="btn-confirm-bulk-download">
                        <span id="confirm-bulk-download-label"><i class="bi bi-unlock me-1"></i>Verify &amp; Download</span>
                        <span id="confirm-bulk-download-spinner" class="spinner-border spinner-border-sm d-none"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        (function () {
            const selectAll = document.getElementById('selectAllCheckbox');
            const rowChecks = () => Array.from(document.querySelectorAll('.row-checkbox'));
            const counter = document.getElementById('selectedCounter');
            const bulkBtn = document.getElementById('btnBulkDownload');

            function refresh() {
                const checked = rowChecks().filter(c => c.checked);
                counter.textContent = checked.length + ' selected';
                bulkBtn.disabled = checked.length === 0;
                if (selectAll) {
                    selectAll.checked = checked.length > 0 && checked.length === rowChecks().length;
                }
            }

            if (selectAll) {
                selectAll.addEventListener('change', function () {
                    rowChecks().forEach(c => { c.checked = selectAll.checked; });
                    refresh();
                });
            }
            document.addEventListener('change', function (e) {
                if (e.target.classList.contains('row-checkbox')) refresh();
            });

            const modalEl = document.getElementById('bulkPasswordModal');
            const modal = new bootstrap.Modal(modalEl);
            const passwordInput = document.getElementById('bulk-archive-password-input');
            const errorEl = document.getElementById('bulk-archive-password-error');
            const confirmBtn = document.getElementById('btn-confirm-bulk-download');
            const label = document.getElementById('confirm-bulk-download-label');
            const spinner = document.getElementById('confirm-bulk-download-spinner');
            const verifyUrl = @json(route('agency.archived_reports.bulk_verify_password'));
            const downloadBaseUrl = @json(route('agency.archived_reports.bulk_download'));
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            bulkBtn.addEventListener('click', function () {
                document.getElementById('bulkPasswordCount').textContent = rowChecks().filter(c => c.checked).length;
                passwordInput.value = '';
                errorEl.classList.add('d-none');
                modal.show();
                setTimeout(() => passwordInput.focus(), 300);
            });

            confirmBtn.addEventListener('click', async function () {
                const password = passwordInput.value;
                if (!password) {
                    errorEl.textContent = 'Password is required.';
                    errorEl.classList.remove('d-none');
                    return;
                }
                const incidentIds = rowChecks().filter(c => c.checked).map(c => c.value);
                if (incidentIds.length === 0) { modal.hide(); return; }

                label.classList.add('d-none');
                spinner.classList.remove('d-none');
                confirmBtn.disabled = true;
                errorEl.classList.add('d-none');

                try {
                    const res = await fetch(verifyUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({ password, incident_ids: incidentIds }),
                    });
                    const data = await res.json();

                    if (!res.ok) {
                        errorEl.textContent = data.message || 'Incorrect password. Please try again.';
                        errorEl.classList.remove('d-none');
                        return;
                    }

                    showLoadingOverlay('Preparing ZIP archive, please wait...');
                    window.location.href = downloadBaseUrl + '?download_token=' + encodeURIComponent(data.download_token);
                    modal.hide();
                    // Direct-download navigations don't blur the window, so the
                    // global focus/pageshow listeners won't fire here — force-hide.
                    setTimeout(hideLoadingOverlay, 4000);
                } catch (e) {
                    errorEl.textContent = 'Something went wrong. Please try again.';
                    errorEl.classList.remove('d-none');
                } finally {
                    label.classList.remove('d-none');
                    spinner.classList.add('d-none');
                    confirmBtn.disabled = false;
                }
            });
        })();
    </script>
    @endpush
</x-app-layout>
