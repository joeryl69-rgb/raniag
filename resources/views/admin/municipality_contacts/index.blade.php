<x-app-layout>
    <x-slot name="header">Referral contacts</x-slot>

    <x-workspace-intro
        eyebrow="COORDINATION / EXTERNAL REFERRALS"
        title="Referral contacts"
        description="Keep verified municipality offices in one place. Select an office from an incident to call or email it and share its public hotline with the reporter."
        icon="bi-telephone-forward"
    />

    <x-kpi-strip :items="[
        ['label' => 'Referral offices', 'value' => $contacts->count(), 'icon' => 'bi-building', 'tone' => 'primary'],
        ['label' => 'Available', 'value' => $contacts->where('is_active', true)->count(), 'icon' => 'bi-telephone-forward', 'tone' => 'success'],
    ]" />

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="status">
            <i class="bi bi-check-circle-fill me-1" aria-hidden="true"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Dismiss"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger" role="alert" id="contact-validation-errors">
            <strong>We couldn’t save this referral contact.</strong>
            <ul class="mb-0 mt-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="rg-table-desk rg-referral-directory" aria-labelledby="referral-contacts-heading">
        <header class="rg-table-desk-head">
            <div>
                <h2 id="referral-contacts-heading">Municipality directory <span>{{ $contacts->count() }} {{ Str::plural('office', $contacts->count()) }}</span></h2>
                <p>Inactive offices remain in the directory for reference but cannot be selected for new referrals.</p>
            </div>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#municipalityContactModal" data-contact-create>
                <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Add office
            </button>
        </header>

        <div class="table-responsive rg-data-table-wrap">
            <table class="table table-hover align-middle mb-0 rg-data-table">
                <thead class="table-light">
                    <tr>
                        <th>Municipality / office</th>
                        <th>Contact channels</th>
                        <th class="text-center">Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($contacts as $contact)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $contact->municipality }}</div>
                                <div>{{ $contact->office_name }}</div>
                                @if ($contact->address)
                                    <div class="small text-muted mt-1"><i class="bi bi-geo-alt me-1" aria-hidden="true"></i>{{ $contact->address }}</div>
                                @endif
                                @if ($contact->latitude !== null && $contact->longitude !== null)
                                    <span class="badge text-bg-light border mt-1"><i class="bi bi-pin-map-fill me-1" aria-hidden="true"></i>Office pin saved</span>
                                @endif
                            </td>
                            <td class="small">
                                @if ($contact->phone)
                                    <div><i class="bi bi-telephone me-1" aria-hidden="true"></i><a href="tel:{{ preg_replace('/[^0-9+*#]/', '', $contact->phone) }}">{{ $contact->phone }}</a></div>
                                @endif
                                @if ($contact->hotline)
                                    <div><i class="bi bi-telephone-forward me-1" aria-hidden="true"></i><a href="tel:{{ preg_replace('/[^0-9+*#]/', '', $contact->hotline) }}">{{ $contact->hotline }}</a> <span class="text-muted">hotline</span></div>
                                @endif
                                @if ($contact->email)
                                    <div><i class="bi bi-envelope me-1" aria-hidden="true"></i><a href="mailto:{{ $contact->email }}">{{ $contact->email }}</a></div>
                                @endif
                                @if (! $contact->phone && ! $contact->hotline && ! $contact->email)
                                    <span class="text-muted">No contact channel saved</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $contact->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                    {{ $contact->is_active ? 'Available' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-end text-nowrap">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-primary js-edit-municipality-contact"
                                    data-contact-id="{{ $contact->id }}"
                                    data-bs-toggle="modal"
                                    data-bs-target="#municipalityContactModal"
                                    aria-label="Edit {{ $contact->office_name }}"
                                >
                                    <i class="bi bi-pencil me-1" aria-hidden="true"></i>Edit
                                </button>
                                <form method="POST" action="{{ route('admin.referral_contacts.destroy', $contact) }}" class="d-inline" onsubmit="return confirm('Remove this referral contact? Existing referrals will keep their saved details.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="Remove {{ $contact->office_name }}">
                                        <i class="bi bi-trash3" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-5">
                                <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-light text-primary mb-3" style="width:52px;height:52px;">
                                    <i class="bi bi-building-add fs-4" aria-hidden="true"></i>
                                </span>
                                <div class="fw-semibold">No referral offices yet</div>
                                <p class="text-muted small mb-3">Add a verified municipality office to enable external referrals.</p>
                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#municipalityContactModal" data-contact-create>
                                    <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Add first office
                                </button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="modal fade" id="municipalityContactModal" tabindex="-1" aria-labelledby="contact-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content rg-contact-modal">
                <form method="POST" action="{{ route('admin.referral_contacts.store') }}" id="municipality-contact-form">
                    @csrf
                    <input type="hidden" name="_method" id="contact-form-method" value="PUT" disabled>
                    <input type="hidden" name="editing_contact_id" id="editing-contact-id" value="{{ old('editing_contact_id') }}">
                    <input type="hidden" name="latitude" id="contact-latitude" value="{{ old('latitude') }}">
                    <input type="hidden" name="longitude" id="contact-longitude" value="{{ old('longitude') }}">
                    <input type="hidden" name="is_active" value="0">

                    <div class="modal-header">
                        <div>
                            <span class="rg-contact-modal-kicker">REFERRAL DIRECTORY</span>
                            <h2 class="modal-title fs-5 fw-bold" id="contact-modal-title">Add referral contact</h2>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="rg-contact-form-section">
                            <div class="rg-contact-form-heading">
                                <span>01</span>
                                <div><strong>Office details</strong><small>Identify the receiving municipality and office.</small></div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="contact-municipality" class="form-label">Municipality <span class="text-danger">*</span></label>
                                    <input name="municipality" id="contact-municipality" class="form-control" maxlength="120" required value="{{ old('municipality') }}" autocomplete="organization">
                                </div>
                                <div class="col-md-6">
                                    <label for="contact-office" class="form-label">Office / agency <span class="text-danger">*</span></label>
                                    <input name="office_name" id="contact-office" class="form-control" maxlength="160" required value="{{ old('office_name') }}" placeholder="e.g. Municipal DRRMO">
                                </div>
                                <div class="col-12">
                                    <label for="contact-address" class="form-label">Office address / landmark <span class="text-muted">(optional)</span></label>
                                    <input name="address" id="contact-address" class="form-control" maxlength="255" value="{{ old('address') }}" placeholder="Municipal hall, street, or nearby landmark">
                                </div>
                            </div>
                        </div>

                        <div class="rg-contact-form-section">
                            <div class="rg-contact-form-heading">
                                <span>02</span>
                                <div><strong>Contact channels</strong><small>At least one phone, email, or hotline is required.</small></div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="contact-phone" class="form-label">Contact number</label>
                                    <input name="phone" id="contact-phone" class="form-control" type="tel" maxlength="40" value="{{ old('phone') }}" placeholder="+63…">
                                </div>
                                <div class="col-md-6">
                                    <label for="contact-hotline" class="form-label">Public hotline</label>
                                    <input name="hotline" id="contact-hotline" class="form-control" type="tel" maxlength="40" value="{{ old('hotline') }}" placeholder="+63…">
                                </div>
                                <div class="col-12">
                                    <label for="contact-email" class="form-label">Email address</label>
                                    <input name="email" id="contact-email" class="form-control" type="email" maxlength="255" value="{{ old('email') }}" placeholder="office@example.gov.ph">
                                </div>
                            </div>
                        </div>

                        <div class="rg-contact-form-section">
                            <div class="rg-contact-form-heading">
                                <span>03</span>
                                <div><strong>Office map pin <small class="fw-normal">(optional)</small></strong><small>Click the map to place the receiving office. Drag the pin to adjust it.</small></div>
                            </div>
                            <div class="rg-contact-map-wrap">
                                <div id="municipality-contact-map" aria-label="Select the municipality office location on the map"></div>
                            </div>
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-2">
                                <span class="small text-muted" id="contact-map-status" aria-live="polite">No office pin set.</span>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="contact-clear-pin">
                                    <i class="bi bi-geo me-1" aria-hidden="true"></i>Clear pin
                                </button>
                            </div>
                        </div>

                        <div class="rg-contact-active-toggle">
                            <div>
                                <strong>Available for new referrals</strong>
                                <small>Turn this off when the office should no longer appear in incident referrals.</small>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="contact-active" value="1" @checked(old('is_active', '1') === '1')>
                                <label class="form-check-label visually-hidden" for="contact-active">Available for new referrals</label>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="contact-save">
                            <i class="bi bi-check2 me-1" aria-hidden="true"></i>Save referral contact
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<style>
    .rg-contact-modal { border:0;border-radius:.85rem;box-shadow:0 24px 70px rgba(10,31,45,.22); }
    .rg-contact-modal .modal-header { padding:1.2rem 1.4rem;border-bottom:1px solid #e6edf2; }
    .rg-contact-modal .modal-body { max-height:72vh;padding:1.3rem 1.4rem; }
    .rg-contact-modal .modal-footer { padding:1rem 1.4rem;border-top:1px solid #e6edf2; }
    .rg-contact-modal-kicker { display:block;margin-bottom:.3rem;color:#1769aa;font-size:.64rem;font-weight:800;letter-spacing:.13em; }
    .rg-contact-form-section + .rg-contact-form-section { margin-top:1.4rem;padding-top:1.3rem;border-top:1px solid #e8edf1; }
    .rg-contact-form-heading { display:flex;align-items:flex-start;gap:.7rem;margin-bottom:1rem; }
    .rg-contact-form-heading > span { display:grid;place-items:center;flex:0 0 1.8rem;height:1.8rem;border-radius:50%;background:#eaf2fb;color:#175ea8;font-size:.72rem;font-weight:800; }
    .rg-contact-form-heading strong,.rg-contact-form-heading small { display:block; }
    .rg-contact-form-heading strong { color:#263d4b;font-size:.9rem; }
    .rg-contact-form-heading small { margin-top:.12rem;color:#758592;font-size:.76rem; }
    .rg-contact-map-wrap { height:230px;overflow:hidden;border:1px solid #d6e0e7;border-radius:.6rem;background:#edf2f5; }
    #municipality-contact-map { width:100%;height:100%; }
    .rg-contact-active-toggle { display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-top:1.3rem;padding:1rem;border:1px solid #e3eaf0;border-radius:.65rem;background:#f8fafc; }
    .rg-contact-active-toggle strong,.rg-contact-active-toggle small { display:block; }
    .rg-contact-active-toggle strong { font-size:.86rem; }
    .rg-contact-active-toggle small { margin-top:.15rem;color:#758592;font-size:.76rem; }
    @media(max-width:575.98px) {
        .rg-contact-modal .modal-header,.rg-contact-modal .modal-body { padding:1rem; }
        .rg-contact-modal .modal-footer { padding:.85rem 1rem; }
        .rg-contact-map-wrap { height:200px; }
        .rg-contact-modal .modal-footer .btn { flex:1; }
    }
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script src="{{ asset('js/raniag-mapbox.js') }}?v={{ @filemtime(public_path('js/raniag-mapbox.js')) }}"></script>
<script>
(() => {
    const contacts = @json($contactMapData);
    const mapConfig = @json($map);
    const form = document.getElementById('municipality-contact-form');
    const modalElement = document.getElementById('municipalityContactModal');
    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
    const originalAction = @json(route('admin.referral_contacts.store'));
    const updateTemplate = @json(route('admin.referral_contacts.update', ['municipalityContact' => '__ID__']));
    const editingInput = document.getElementById('editing-contact-id');
    const methodInput = document.getElementById('contact-form-method');
    const latitudeInput = document.getElementById('contact-latitude');
    const longitudeInput = document.getElementById('contact-longitude');
    const status = document.getElementById('contact-map-status');
    const title = document.getElementById('contact-modal-title');
    const saveButton = document.getElementById('contact-save');
    let map = null;
    let selectedMarker = null;
    let editingId = null;

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[character]));

    function clearDraftPin() {
        if (map && selectedMarker) map.removeLayer(selectedMarker);
        selectedMarker = null;
    }

    function updatePinStatus(latitude, longitude) {
        status.textContent = `Office pin set at ${Number(latitude).toFixed(5)}, ${Number(longitude).toFixed(5)}.`;
    }

    function setPin(latitude, longitude) {
        latitudeInput.value = Number(latitude).toFixed(7);
        longitudeInput.value = Number(longitude).toFixed(7);
        clearDraftPin();
        selectedMarker = L.marker([latitude, longitude], { draggable: true }).addTo(map);
        selectedMarker.on('dragend', () => {
            const point = selectedMarker.getLatLng();
            latitudeInput.value = point.lat.toFixed(7);
            longitudeInput.value = point.lng.toFixed(7);
            updatePinStatus(point.lat, point.lng);
        });
        updatePinStatus(latitude, longitude);
    }

    function initializeMap() {
        if (map) {
            window.setTimeout(() => map.invalidateSize(), 100);
            return;
        }

        map = L.map('municipality-contact-map').setView(
            [mapConfig.default_lat, mapConfig.default_lng],
            mapConfig.default_zoom
        );
        window.RaniagMapScroll?.guard(map);
        if (window.RANIAG_Mapbox) {
            window.RANIAG_Mapbox.addBasemap(map, mapConfig);
        } else {
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap',
            }).addTo(map);
        }

        contacts.forEach((contact) => {
            if (contact.latitude === null || contact.longitude === null) return;
            L.marker([Number(contact.latitude), Number(contact.longitude)])
                .bindTooltip(`${escapeHtml(contact.office_name)} · ${escapeHtml(contact.municipality)}`)
                .addTo(map);
        });
        map.on('click', (event) => setPin(event.latlng.lat, event.latlng.lng));
        window.setTimeout(() => map.invalidateSize(), 100);
    }

    function restoreMapForCurrentContact() {
        initializeMap();
        clearDraftPin();
        const latitude = latitudeInput.value.trim();
        const longitude = longitudeInput.value.trim();
        if (latitude && longitude && Number.isFinite(Number(latitude)) && Number.isFinite(Number(longitude))) {
            setPin(Number(latitude), Number(longitude));
            map.setView([Number(latitude), Number(longitude)], Math.max(map.getZoom(), 14));
        } else {
            latitudeInput.value = '';
            longitudeInput.value = '';
            status.textContent = 'No office pin set. Click the map to place one.';
            map.setView([mapConfig.default_lat, mapConfig.default_lng], mapConfig.default_zoom);
        }
    }

    function setCreateMode(resetFields) {
        if (resetFields) {
            form.reset();
            document.getElementById('contact-municipality').value = '';
            document.getElementById('contact-office').value = '';
            document.getElementById('contact-phone').value = '';
            document.getElementById('contact-email').value = '';
            document.getElementById('contact-hotline').value = '';
            document.getElementById('contact-address').value = '';
            latitudeInput.value = '';
            longitudeInput.value = '';
            document.getElementById('contact-active').checked = true;
        }
        editingId = null;
        editingInput.value = '';
        form.action = originalAction;
        methodInput.disabled = true;
        methodInput.removeAttribute('name');
        title.textContent = 'Add referral contact';
        saveButton.innerHTML = '<i class="bi bi-check2 me-1" aria-hidden="true"></i>Save referral contact';
    }

    function setEditMode(contactId, contact, preserveValues) {
        editingId = String(contactId);
        editingInput.value = contactId;
        form.action = updateTemplate.replace('__ID__', contactId);
        methodInput.disabled = false;
        methodInput.name = '_method';
        methodInput.value = 'PUT';
        if (contact && !preserveValues) {
            document.getElementById('contact-municipality').value = contact.municipality || '';
            document.getElementById('contact-office').value = contact.office_name || '';
            document.getElementById('contact-phone').value = contact.phone || '';
            document.getElementById('contact-email').value = contact.email || '';
            document.getElementById('contact-hotline').value = contact.hotline || '';
            document.getElementById('contact-address').value = contact.address || '';
            latitudeInput.value = contact.latitude ?? '';
            longitudeInput.value = contact.longitude ?? '';
            document.getElementById('contact-active').checked = !!contact.is_active;
        }
        title.textContent = contact ? `Edit ${contact.office_name}` : 'Edit referral contact';
        saveButton.innerHTML = '<i class="bi bi-check2 me-1" aria-hidden="true"></i>Save changes';
    }

    document.querySelectorAll('[data-contact-create]').forEach((button) => {
        button.addEventListener('click', () => setCreateMode(true));
    });
    document.querySelectorAll('.js-edit-municipality-contact').forEach((button) => {
        button.addEventListener('click', () => {
            const contact = contacts.find((item) => String(item.id) === button.dataset.contactId);
            if (!contact) return;
            setEditMode(contact.id, contact, false);
        });
    });

    document.getElementById('contact-clear-pin').addEventListener('click', () => {
        latitudeInput.value = '';
        longitudeInput.value = '';
        clearDraftPin();
        status.textContent = 'No office pin set. Click the map to place one.';
        map?.setView([mapConfig.default_lat, mapConfig.default_lng], mapConfig.default_zoom);
    });

    modalElement.addEventListener('shown.bs.modal', restoreMapForCurrentContact);
    modalElement.addEventListener('hidden.bs.modal', clearDraftPin);

    const oldContactId = @json(old('editing_contact_id'));
    if (@json($errors->any())) {
        if (oldContactId) {
            setEditMode(oldContactId, contacts.find((item) => String(item.id) === String(oldContactId)), true);
        } else {
            setCreateMode(false);
        }
        modal.show();
    }
})();
</script>
@endpush
</x-app-layout>
