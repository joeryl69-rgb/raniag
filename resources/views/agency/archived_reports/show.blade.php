<x-app-layout>
    <x-slot name="header">
        {{ __('Archived Report') }} &mdash; {{ $incident->tracking_number }}
    </x-slot>

    <div class="d-flex mb-4">
        <a href="{{ route('agency.archived_reports.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Archived Reports
        </a>
    </div>

    <div id="archive-alert" class="d-none alert" role="alert"></div>

    <div class="card raniag-card shadow-sm border-0 mb-4">
        <div class="card-header raniag-card-header py-3 border-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="mb-1 fw-bold"><i class="bi bi-folder2-open text-success me-2"></i>{{ $incident->tracking_number }}</h5>
                <span class="badge {{ $incident->status->value === 'resolved' ? 'bg-success' : 'bg-secondary' }}">{{ $incident->status->label() }}</span>
            </div>
            <button type="button" class="btn btn-success" id="btn-open-password-modal">
                <i class="bi bi-file-earmark-zip me-1"></i>Download Archive (.zip)
            </button>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="text-muted small">Incident Type</div>
                    <div class="fw-semibold">{{ $incident->incidentType?->name }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Barangay</div>
                    <div class="fw-semibold">{{ $incident->barangay }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Reported At</div>
                    <div class="fw-semibold">{{ $incident->reported_at?->format('M d, Y H:i') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card raniag-card shadow-sm border-0 mb-4">
        <div class="card-header raniag-card-header py-3 border-0">
            <h6 class="mb-0 fw-bold"><i class="bi bi-file-earmark-text me-2"></i>Filed Documents ({{ $incident->incidentDocuments->count() }})</h6>
        </div>
        <ul class="list-group list-group-flush">
            @forelse($incident->incidentDocuments as $doc)
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>{{ $doc->original_filename }}</span>
                    <span class="text-muted small">{{ $doc->document_type->value ?? $doc->document_type }}</span>
                </li>
            @empty
                <li class="list-group-item text-muted">No documents filed.</li>
            @endforelse
        </ul>
    </div>

    <div class="card raniag-card shadow-sm border-0">
        <div class="card-header raniag-card-header py-3 border-0">
            <h6 class="mb-0 fw-bold"><i class="bi bi-image me-2"></i>Evidence ({{ $incident->evidence->count() }})</h6>
        </div>
        <ul class="list-group list-group-flush">
            @forelse($incident->evidence as $ev)
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>{{ $ev->original_filename }}</span>
                    <span class="text-muted small">{{ $ev->caption }}</span>
                </li>
            @empty
                <li class="list-group-item text-muted">No evidence attached.</li>
            @endforelse
        </ul>
    </div>

    {{-- Password re-confirmation modal, required before any ZIP transfer --}}
    <div class="modal fade" id="passwordConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-shield-lock me-2"></i>Confirm Your Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">For security, please re-enter your account password to download this archive.</p>
                    <input type="password" id="archive-password-input" class="form-control" placeholder="Password" autocomplete="current-password">
                    <div id="archive-password-error" class="text-danger small mt-2 d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="btn-confirm-download">
                        <span id="confirm-download-label"><i class="bi bi-unlock me-1"></i>Verify &amp; Download</span>
                        <span id="confirm-download-spinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        (function () {
            const openBtn = document.getElementById('btn-open-password-modal');
            const modalEl = document.getElementById('passwordConfirmModal');
            const modal = new bootstrap.Modal(modalEl);
            const passwordInput = document.getElementById('archive-password-input');
            const errorEl = document.getElementById('archive-password-error');
            const confirmBtn = document.getElementById('btn-confirm-download');
            const label = document.getElementById('confirm-download-label');
            const spinner = document.getElementById('confirm-download-spinner');
            const alertBox = document.getElementById('archive-alert');
            const verifyUrl = @json(route('agency.archived_reports.verify_password', $incident->id));
            const downloadBaseUrl = @json(route('agency.archived_reports.download', $incident->id));
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            function showAlert(message, type) {
                alertBox.textContent = message;
                alertBox.className = 'alert alert-' + type;
            }

            openBtn.addEventListener('click', () => {
                passwordInput.value = '';
                errorEl.classList.add('d-none');
                modal.show();
                setTimeout(() => passwordInput.focus(), 300);
            });

            confirmBtn.addEventListener('click', async () => {
                const password = passwordInput.value;
                if (!password) {
                    errorEl.textContent = 'Password is required.';
                    errorEl.classList.remove('d-none');
                    return;
                }

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
                        body: JSON.stringify({ password }),
                    });
                    const data = await res.json();

                    if (!res.ok) {
                        errorEl.textContent = data.message || 'Incorrect password. Please try again.';
                        errorEl.classList.remove('d-none');
                        return;
                    }

                    // Password confirmed — trigger the native file-save dialog.
                    showLoadingOverlay('Preparing ZIP archive, please wait...');
                    const url = downloadBaseUrl + '?download_token=' + encodeURIComponent(data.download_token);
                    window.location.href = url;
                    // Direct-download navigations don't blur the window, so the
                    // global focus/pageshow listeners won't fire here — force-hide.
                    setTimeout(hideLoadingOverlay, 4000);

                    modal.hide();
                    showAlert('Password verified. Your download should begin shortly.', 'success');
                } catch (e) {
                    errorEl.textContent = 'Something went wrong. Please try again.';
                    errorEl.classList.remove('d-none');
                } finally {
                    label.classList.remove('d-none');
                    spinner.classList.add('d-none');
                    confirmBtn.disabled = false;
                }
            });

            passwordInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') confirmBtn.click();
            });
        })();
    </script>
    @endpush
</x-app-layout>
