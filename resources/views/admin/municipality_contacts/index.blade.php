<x-app-layout>
    <x-slot name="header">Referral contacts</x-slot>

    <x-workspace-intro
        eyebrow="COORDINATION / EXTERNAL REFERRALS"
        title="Referral contacts"
        description="Maintain municipality offices and their contact details. Select an office from an incident to call or email it and publish its hotline and optional map location with the referral."
        icon="bi-telephone-forward"
    />

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <strong>Check the contact details below.</strong>
            <ul class="mb-0 mt-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-xl-5">
            <section class="card raniag-card rg-app-card">
                <div class="card-header raniag-card-header py-3">
                    <strong id="contact-form-title">Add a municipality office</strong>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.referral_contacts.store') }}" id="municipality-contact-form">
                        @csrf
                        <input type="hidden" name="_method" id="contact-form-method" value="PUT" disabled>
                        <input type="hidden" name="editing_contact_id" id="editing-contact-id" value="{{ old('editing_contact_id') }}">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="contact-municipality" class="form-label">Municipality</label>
                                <input name="municipality" id="contact-municipality" class="form-control" maxlength="120" required value="{{ old('municipality') }}">
                            </div>
                            <div class="col-md-6">
                                <label for="contact-office" class="form-label">Office / agency</label>
                                <input name="office_name" id="contact-office" class="form-control" maxlength="160" required value="{{ old('office_name') }}">
                            </div>
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
                                <input name="email" id="contact-email" class="form-control" type="email" maxlength="255" value="{{ old('email') }}">
                                <div class="form-text">Provide at least one of contact number, email, or public hotline.</div>
                            </div>
                            <div class="col-12">
                                <label for="contact-address" class="form-label">Office address / landmark</label>
                                <input name="address" id="contact-address" class="form-control" maxlength="255" value="{{ old('address') }}">
                            </div>
                            <div class="col-md-6">
                                <label for="contact-latitude" class="form-label">Map latitude <span class="text-muted">(optional)</span></label>
                                <input name="latitude" id="contact-latitude" class="form-control" type="number" step="any" min="-90" max="90" value="{{ old('latitude') }}">
                            </div>
                            <div class="col-md-6">
                                <label for="contact-longitude" class="form-label">Map longitude <span class="text-muted">(optional)</span></label>
                                <input name="longitude" id="contact-longitude" class="form-control" type="number" step="any" min="-180" max="180" value="{{ old('longitude') }}">
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input type="hidden" name="is_active" value="0">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="contact-active" value="1" @checked(old('is_active', '1') === '1')>
                                    <label class="form-check-label" for="contact-active">Available for new referrals</label>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2 mt-3">
                            <button type="submit" class="btn btn-primary" id="contact-save">Add contact</button>
                            <button type="button" class="btn btn-outline-secondary d-none" id="contact-cancel-edit">Cancel edit</button>
                        </div>
                    </form>
                </div>
            </section>

            <section class="card raniag-card rg-app-card mt-4">
                <div class="card-header raniag-card-header py-3"><strong>Office map pins</strong></div>
                <div class="card-body">
                    <p class="small text-muted mb-2">Select a contact above, then click the map to set or move its office pin. Coordinates may also be entered directly.</p>
                    <div id="municipality-contact-map" style="height:320px;border-radius:.5rem;"></div>
                    <div class="small text-muted mt-2" id="contact-map-status" aria-live="polite">Map pin is optional. Only the selected office pin is shown on a referred report's private tracking page.</div>
                </div>
            </section>
        </div>

        <div class="col-xl-7">
            <section class="rg-table-desk">
                <header class="rg-table-desk-head">
                    <div>
                        <h2 id="referral-contacts-heading">Saved referral offices <span>{{ $contacts->count() }} contacts</span></h2>
                        <p>Inactive offices remain in the directory but cannot be selected for new referrals.</p>
                    </div>
                </header>
                <div class="table-responsive rg-data-table-wrap">
                    <table class="table table-hover align-middle mb-0 rg-data-table">
                        <thead class="table-light">
                            <tr>
                                <th>Municipality / office</th>
                                <th>Contact channels</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($contacts as $contact)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $contact->municipality }}</div>
                                        <div>{{ $contact->office_name }}</div>
                                        @if ($contact->address)<div class="small text-muted">{{ $contact->address }}</div>@endif
                                        @if ($contact->latitude !== null && $contact->longitude !== null)
                                            <span class="badge text-bg-light border mt-1"><i class="bi bi-geo-alt-fill me-1"></i>Map pin set</span>
                                        @endif
                                    </td>
                                    <td class="small">
                                        @if ($contact->phone)<div><i class="bi bi-telephone me-1"></i><a href="tel:{{ preg_replace('/[^0-9+*#]/', '', $contact->phone) }}">{{ $contact->phone }}</a></div>@endif
                                        @if ($contact->hotline)<div><i class="bi bi-telephone-forward me-1"></i><a href="tel:{{ preg_replace('/[^0-9+*#]/', '', $contact->hotline) }}">{{ $contact->hotline }}</a> <span class="text-muted">hotline</span></div>@endif
                                        @if ($contact->email)<div><i class="bi bi-envelope me-1"></i><a href="mailto:{{ $contact->email }}">{{ $contact->email }}</a></div>@endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $contact->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $contact->is_active ? 'Available' : 'Inactive' }}</span>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <button type="button" class="btn btn-sm btn-outline-primary js-edit-municipality-contact" data-contact-id="{{ $contact->id }}">Edit</button>
                                        <form method="POST" action="{{ route('admin.referral_contacts.destroy', $contact) }}" class="d-inline" onsubmit="return confirm('Remove this referral contact? Existing referrals will keep their saved details.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="Remove {{ $contact->office_name }}"><i class="bi bi-trash3"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">No municipality contacts yet. Add the first office to enable external referrals.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script src="{{ asset('js/raniag-mapbox.js') }}?v={{ @filemtime(public_path('js/raniag-mapbox.js')) }}"></script>
<script>
(() => {
    const contacts = @json($contactMapData);
    const mapConfig = @json($map);
    const form = document.getElementById('municipality-contact-form');
    const originalAction = @json(route('admin.referral_contacts.store'));
    const updateTemplate = @json(route('admin.referral_contacts.update', ['municipalityContact' => '__ID__']));
    const editingInput = document.getElementById('editing-contact-id');
    const methodInput = document.getElementById('contact-form-method');
    const latitudeInput = document.getElementById('contact-latitude');
    const longitudeInput = document.getElementById('contact-longitude');
    const status = document.getElementById('contact-map-status');
    let editingId = null;
    let selectedMarker = null;
    const savedMarkers = new Map();
    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[character]));
    const map = L.map('municipality-contact-map').setView(
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
        const marker = L.marker([Number(contact.latitude), Number(contact.longitude)])
            .bindTooltip(`${escapeHtml(contact.office_name)} · ${escapeHtml(contact.municipality)}`);
        if (contact.is_active) marker.addTo(map);
        savedMarkers.set(String(contact.id), marker);
    });

    function clearDraftPin() {
        if (selectedMarker) map.removeLayer(selectedMarker);
        selectedMarker = null;
    }

    function setCoordinates(latitude, longitude) {
        latitudeInput.value = Number(latitude).toFixed(7);
        longitudeInput.value = Number(longitude).toFixed(7);
        clearDraftPin();
        selectedMarker = L.marker([latitude, longitude], { draggable: true }).addTo(map);
        selectedMarker.on('dragend', () => {
            const point = selectedMarker.getLatLng();
            latitudeInput.value = point.lat.toFixed(7);
            longitudeInput.value = point.lng.toFixed(7);
        });
        status.textContent = `Office pin set at ${Number(latitude).toFixed(5)}, ${Number(longitude).toFixed(5)}.`;
    }

    function syncCoordinates() {
        const latitude = latitudeInput.value.trim();
        const longitude = longitudeInput.value.trim();
        if (latitude && longitude && Number.isFinite(Number(latitude)) && Number.isFinite(Number(longitude))) {
            setCoordinates(Number(latitude), Number(longitude));
        } else {
            clearDraftPin();
            status.textContent = 'Enter both coordinates or click the map to set the office pin.';
        }
    }

    latitudeInput.addEventListener('change', syncCoordinates);
    longitudeInput.addEventListener('change', syncCoordinates);
    map.on('click', (event) => {
        setCoordinates(event.latlng.lat, event.latlng.lng);
    });

    function beginEdit(contact, preserveValues = false) {
        if (!contact) return;
        if (editingId && editingId !== String(contact.id)) {
            const previousMarker = savedMarkers.get(editingId);
            if (previousMarker && contacts.find((item) => String(item.id) === editingId)?.is_active) previousMarker.addTo(map);
        }
        clearDraftPin();
        editingId = String(contact.id);
        editingInput.value = contact.id;
        form.action = updateTemplate.replace('__ID__', contact.id);
        methodInput.disabled = false;
        methodInput.name = '_method';
        methodInput.value = 'PUT';
        if (!preserveValues) {
            document.getElementById('contact-municipality').value = contact.municipality || '';
            document.getElementById('contact-office').value = contact.office_name || '';
            document.getElementById('contact-phone').value = contact.phone || '';
            document.getElementById('contact-email').value = contact.email || '';
            document.getElementById('contact-hotline').value = contact.hotline || '';
            document.getElementById('contact-address').value = contact.address || '';
            latitudeInput.value = contact.latitude || '';
            longitudeInput.value = contact.longitude || '';
            document.getElementById('contact-active').checked = !!contact.is_active;
        }
        document.getElementById('contact-form-title').textContent = `Edit ${contact.office_name}`;
        document.getElementById('contact-save').textContent = 'Save changes';
        document.getElementById('contact-cancel-edit').classList.remove('d-none');
        const saved = savedMarkers.get(editingId);
        if (saved && map.hasLayer(saved)) map.removeLayer(saved);
        if (latitudeInput.value && longitudeInput.value) {
            syncCoordinates();
            map.setView([Number(latitudeInput.value), Number(longitudeInput.value)], Math.max(map.getZoom(), 14));
        }
        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function cancelEdit() {
        if (editingId) {
            const saved = savedMarkers.get(editingId);
            if (saved && contacts.find((contact) => String(contact.id) === editingId)?.is_active) saved.addTo(map);
        }
        editingId = null;
        form.action = originalAction;
        methodInput.disabled = true;
        methodInput.removeAttribute('name');
        editingInput.value = '';
        clearDraftPin();
        form.reset();
        document.getElementById('contact-form-title').textContent = 'Add a municipality office';
        document.getElementById('contact-save').textContent = 'Add contact';
        document.getElementById('contact-cancel-edit').classList.add('d-none');
        latitudeInput.value = '';
        longitudeInput.value = '';
        status.textContent = 'Map pin is optional. Click the map to set it for the office being added.';
    }

    document.querySelectorAll('.js-edit-municipality-contact').forEach((button) => {
        button.addEventListener('click', () => {
            beginEdit(contacts.find((contact) => String(contact.id) === button.dataset.contactId));
        });
    });
    document.getElementById('contact-cancel-edit').addEventListener('click', cancelEdit);

    const oldContactId = @json(old('editing_contact_id'));
    if (oldContactId) {
        beginEdit(contacts.find((contact) => String(contact.id) === String(oldContactId)), true);
    } else if (latitudeInput.value && longitudeInput.value) {
        setCoordinates(Number(latitudeInput.value), Number(longitudeInput.value));
    }
    setTimeout(() => map.invalidateSize(), 100);
})();
</script>
@endpush
</x-app-layout>
