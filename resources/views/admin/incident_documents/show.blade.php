<x-app-layout>
    <x-slot name="header">{{ __('Case Documents') }}</x-slot>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <a href="{{ route('admin.incident_documents.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back to case files
        </a>
        <a href="{{ route('admin.incidents.show', $incident) }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-exclamation-circle me-1"></i>Open the live incident
        </a>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <div class="fw-bold font-monospace">{{ $incident->tracking_number }}</div>
                <div class="text-muted small">{{ $incident->incidentType->name ?? 'No type' }}@if($incident->barangay) · {{ $incident->barangay }}@endif</div>
            </div>
            <span class="badge bg-primary-subtle text-primary border text-capitalize">{{ $incident->status->value }}</span>
        </div>
    </div>

    <p class="small text-muted">Photograph each paper form. This page only stores the case file. Dispatch, the map, and the reporter stay on the incident.</p>

    @php
        $existingDocuments = $incident->incidentDocuments ?? collect();
        $typeValue = function ($doc) {
            return is_object($doc->document_type) ? $doc->document_type->value : $doc->document_type;
        };
        $filedTypes = $existingDocuments->map($typeValue)->unique();
    @endphp

    <div class="d-flex justify-content-between small text-muted mb-2">
        <span>Forms on file</span>
        <span>{{ $filedTypes->count() }} / {{ count($documentTypes) }}</span>
    </div>
    <div class="progress mb-3" style="height: 6px;">
        <div class="progress-bar {{ $filedTypes->count() >= count($documentTypes) ? 'bg-success' : ($filedTypes->count() > 0 ? 'bg-warning' : 'bg-secondary') }}" style="width: {{ count($documentTypes) ? round(($filedTypes->count() / count($documentTypes)) * 100) : 0 }}%"></div>
    </div>

    <div class="row g-3">
        @foreach ($documentTypes as $docType)
            @php $docsOfType = $existingDocuments->filter(fn ($doc) => $typeValue($doc) === $docType->value); @endphp
            <div class="col-12 col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <span class="fw-semibold">{{ $docType->label() }}</span>
                            <span class="badge {{ $docsOfType->isEmpty() ? 'text-bg-light border text-muted' : 'text-bg-success' }}">{{ $docsOfType->isEmpty() ? 'Missing' : $docsOfType->count().' on file' }}</span>
                        </div>

                        @if ($docsOfType->isNotEmpty())
                            <div class="rg-docthumb-row mb-3">
                                @foreach ($docsOfType as $doc)
                                    <div class="rg-docthumb">
                                        <a href="{{ $doc->url() }}" class="js-lightbox rg-docthumb-link" data-group="casedoc-{{ $incident->id }}-{{ $docType->value }}" data-caption="{{ $docType->label() }}">
                                            @if (str_starts_with((string) $doc->mime_type, 'image/'))
                                                <img src="{{ $doc->url() }}" alt="{{ $docType->label() }}" class="rg-docthumb-img" loading="lazy">
                                            @else
                                                <div class="rg-docthumb-img rg-docthumb-file"><i class="bi bi-file-earmark-pdf fs-4"></i></div>
                                            @endif
                                        </a>
                                        <form method="POST" action="{{ route('admin.incidents.documents.destroy', [$incident->id, $doc->id]) }}" class="rg-docthumb-delform" onsubmit="return confirm('Remove this photo from the case file?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger rg-docthumb-delbtn" title="Remove"><i class="bi bi-x"></i></button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="openScanModal(@js($docType->value), @js($docType->label()), true)"><i class="bi bi-camera me-1"></i>Take photo</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="openScanModal(@js($docType->value), @js($docType->label()), false)"><i class="bi bi-upload me-1"></i>Upload image</button>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="modal fade" id="scanModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" id="scanForm" action="{{ route('admin.incidents.documents.store', $incident) }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="document_type" id="scanDocType">
                    <input type="hidden" name="is_camera_capture" id="scanIsCamera">
                    <div class="modal-header">
                        <h5 class="modal-title">Add photo of <span id="scanDocLabel" class="fw-bold"></span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="small text-muted mb-3">The photo is stored with this case. It is not added to the public evidence on the incident.</p>
                        <div class="d-flex gap-2 align-items-start mb-3">
                            <input type="file" name="file" id="scanFileInput" class="form-control" accept="image/*" required>
                            <button type="button" class="btn btn-outline-secondary flex-shrink-0 d-none" id="scanRetakeBtn">Retake</button>
                        </div>
                        <div id="scanPreviewWrap" class="d-none text-center">
                            <img id="scanPreviewImg" class="img-fluid rounded border" style="max-height:280px;" alt="Document photo preview">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save to case file</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="{{ asset('js/document-camera.js') }}?v={{ @filemtime(public_path('js/document-camera.js')) }}"></script>
    <script>
        let scanIsCameraFlow = false;

        function scanFileFromCamera(file) {
            const fileInput = document.getElementById('scanFileInput');
            const dt = new DataTransfer();
            dt.items.add(file);
            fileInput.files = dt.files;
            fileInput.dispatchEvent(new Event('change'));
            bootstrap.Modal.getOrCreateInstance(document.getElementById('scanModal')).show();
        }

        function openScanModal(docTypeValue, docLabel, isCamera) {
            document.getElementById('scanDocType').value = docTypeValue;
            document.getElementById('scanIsCamera').value = isCamera ? '1' : '0';
            document.getElementById('scanDocLabel').textContent = docLabel;
            document.getElementById('scanPreviewWrap').classList.add('d-none');
            scanIsCameraFlow = !!isCamera;
            document.getElementById('scanRetakeBtn').classList.toggle('d-none', !isCamera);
            const fileInput = document.getElementById('scanFileInput');
            fileInput.value = '';
            if (isCamera) {
                window.RaniagDocCamera.open(scanFileFromCamera);
                return;
            }
            bootstrap.Modal.getOrCreateInstance(document.getElementById('scanModal')).show();
        }

        document.getElementById('scanRetakeBtn').addEventListener('click', function () {
            if (scanIsCameraFlow) {
                window.RaniagDocCamera.open(scanFileFromCamera);
            } else {
                document.getElementById('scanFileInput').value = '';
                document.getElementById('scanPreviewWrap').classList.add('d-none');
            }
        });

        document.getElementById('scanFileInput').addEventListener('change', function (e) {
            const file = e.target.files[0];
            const previewWrap = document.getElementById('scanPreviewWrap');
            const previewImg = document.getElementById('scanPreviewImg');
            if (!file || !file.type.startsWith('image/')) {
                previewWrap.classList.add('d-none');
                return;
            }
            const reader = new FileReader();
            reader.onload = function (evt) {
                previewImg.src = evt.target.result;
                previewWrap.classList.remove('d-none');
            };
            reader.readAsDataURL(file);
        });
    </script>
    @endpush
</x-app-layout>
