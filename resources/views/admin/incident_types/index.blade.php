<x-app-layout>
    <x-slot name="header">
        {{ __('Incident Types') }}
    </x-slot>

<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <p class="small text-muted mb-0">Types used for incident reporting, dashboard breakdowns, and map markers.</p>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#typeModal" onclick="openCreateModal()">
        <i class="bi bi-plus-lg me-1"></i>Add Incident Type
    </button>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:60px;">Icon</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th class="text-center">In Use</th>
                    <th class="text-center">Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($types as $type)
                    <tr>
                        <td>
                            <x-incident-type-badge :type="$type" />
                        </td>
                        <td class="fw-semibold">{{ $type->name }}</td>
                        <td class="text-muted small">{{ Str::limit($type->description, 60) ?: '—' }}</td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border">{{ $type->incidents_count }}</span>
                        </td>
                        <td class="text-center">
                            <form method="POST" action="{{ route('admin.incident_types.toggle', $type) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm {{ $type->is_active ? 'btn-outline-success' : 'btn-outline-secondary' }}">
                                    {{ $type->is_active ? 'Active' : 'Inactive' }}
                                </button>
                            </form>
                        </td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                onclick='openEditModal(@json($type))'>
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form method="POST" action="{{ route('admin.incident_types.destroy', $type) }}" class="d-inline" onsubmit="return confirm('Delete &quot;{{ $type->name }}&quot;? This cannot be undone.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" {{ $type->incidents_count > 0 ? 'disabled title=Cannot delete a type already used by incidents' : '' }}>
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No incident types yet. Add one to get started.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Create / Edit Modal (shared) --}}
<div class="modal fade" id="typeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="typeForm">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">
                <div class="modal-header">
                    <h6 class="modal-title fw-bold" id="typeModalTitle">Add Incident Type</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Name</label>
                        <input type="text" name="name" id="typeName" class="form-control" required maxlength="100">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Description <span class="text-muted fw-normal">(optional)</span></label>
                        <textarea name="description" id="typeDescription" class="form-control" rows="2" maxlength="500"></textarea>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <label class="form-label small fw-semibold mb-2">Icon</label>
                            <button type="button" id="iconResetBtn" class="btn btn-link btn-sm p-0 text-decoration-none d-none" onclick="resetToDefault()">
                                <i class="bi bi-arrow-counterclockwise me-1"></i>Reset to Default
                            </button>
                        </div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span id="iconPreview" class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width:42px;height:42px;background:#f1f5f9;">
                                <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                            </span>
                            <input type="hidden" name="icon" id="typeIcon" required>
                            <input type="text" id="iconSearch" class="form-control form-control-sm" placeholder="Search icons (e.g. flood, alert, medic)…" oninput="filterIcons()">
                        </div>
                        {{-- Every icon the admin can pick is bundled with the app itself
                             (public/vendor/bootstrap-icons) and searchable right here —
                             nothing ever requires leaving RANIAG to find an icon. --}}
                        <div class="d-flex flex-wrap gap-2" id="iconGrid" style="max-height:170px; overflow-y:auto;">
                            @foreach($iconChoices as $icon)
                                <label class="icon-choice" data-icon="{{ $icon }}" title="{{ $icon }}">
                                    <span class="d-inline-flex align-items-center justify-content-center rounded" style="width:36px;height:36px;">
                                        <i class="bi {{ $icon }}"></i>
                                    </span>
                                </label>
                            @endforeach
                            @foreach($iconCatalog as $group => $icons)
                                @foreach($icons as $icon)
                                    @if(!in_array($icon, $iconChoices))
                                        <label class="icon-choice icon-choice--extra d-none" data-icon="{{ $icon }}" data-group="{{ $group }}" title="{{ $icon }} · {{ $group }}">
                                            <span class="d-inline-flex align-items-center justify-content-center rounded" style="width:36px;height:36px;">
                                                <i class="bi {{ $icon }}"></i>
                                            </span>
                                        </label>
                                    @endif
                                @endforeach
                            @endforeach
                        </div>
                        <div class="form-text">Search above to browse every built-in icon, grouped by category (Fire &amp; Hazards, Water &amp; Weather, Medical, etc.) — all bundled with the system, so nothing opens outside RANIAG.</div>
                    </div>
                    <div class="mb-1">
                        <label class="form-label small fw-semibold">Marker Color</label>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            @foreach($colorChoices as $hex => $label)
                                <label class="color-choice" title="{{ $label }}">
                                    <input type="radio" name="color_preset" value="{{ $hex }}" class="d-none" onchange="selectPresetColor(this.value)">
                                    <span class="d-inline-block rounded-circle" style="width:28px;height:28px;background:{{ $hex }};"></span>
                                </label>
                            @endforeach
                            <span class="vr mx-1"></span>
                            <label class="color-choice" title="Custom color">
                                <input type="color" id="typeColorWheel" class="rg-color-wheel-input" oninput="selectPresetColor(this.value)">
                                <span class="d-inline-flex align-items-center justify-content-center rounded-circle border" style="width:28px;height:28px;" onclick="document.getElementById('typeColorWheel').click()">
                                    <i class="bi bi-palette-fill small"></i>
                                </span>
                            </label>
                            <input type="text" name="color" id="typeColor" class="form-control form-control-sm" style="width:110px;" placeholder="#hexcode" oninput="updatePreview()" maxlength="16" required>
                        </div>
                        <p class="form-text mb-0">Use a preset swatch, the color wheel, or type any hex code — the palette isn't limited to the presets above.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Incident Type</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('styles')
<style>
    .icon-choice, .color-choice { cursor: pointer; }
    .icon-choice.is-selected span { background: var(--raniag-primary-light); color: var(--raniag-primary); box-shadow: 0 0 0 2px var(--raniag-primary) inset; border-radius: .4rem; }
    .color-choice input:checked + span { box-shadow: 0 0 0 3px #fff, 0 0 0 5px var(--raniag-ink); }

    /* The native color input must stay part of the page layout (not
       display:none) or the browser's native color-picker popup has no
       real on-screen position to anchor to and opens floating at the
       top-left of the viewport instead of next to the palette swatch. */
    .color-choice { position: relative; }
    .rg-color-wheel-input {
        position: absolute;
        inset: 0;
        width: 28px;
        height: 28px;
        opacity: 0;
        border: 0;
        padding: 0;
        pointer-events: none;
    }
</style>
@endpush

@push('scripts')
<script>
    const createAction = @json(route('admin.incident_types.store'));
    // Each type now carries its OWN default_icon/default_color (set the
    // moment it was created — see the migration + controller), so reset
    // works for every type, not just the 8 originally-seeded ones.
    let currentDefault = null;

    function resetForm() {
        document.getElementById('typeForm').reset();
        document.querySelectorAll('.icon-choice').forEach(el => el.classList.remove('is-selected'));
        document.getElementById('typeIcon').value = '';
        document.getElementById('iconSearch').value = '';
        document.getElementById('typeColor').value = '';
        currentDefault = null;
        document.getElementById('iconResetBtn').classList.add('d-none');
        filterIcons();
        updatePreview();
    }

    function openCreateModal() {
        resetForm();
        document.getElementById('typeModalTitle').textContent = 'Add Incident Type';
        document.getElementById('typeForm').action = createAction;
        document.getElementById('formMethod').value = 'POST';
    }

    function openEditModal(type) {
        resetForm();
        document.getElementById('typeModalTitle').textContent = 'Edit Incident Type';
        document.getElementById('typeName').value = type.name;
        document.getElementById('typeDescription').value = type.description || '';

        selectIcon(type.icon);
        document.getElementById('typeColor').value = type.color;

        // Every type (seeded or custom, old or new) now has its own
        // default_icon/default_color captured at creation time, so the
        // reset button shows whenever we actually have one on record —
        // and is only hidden if the icon/color already match it.
        currentDefault = (type.default_icon && type.default_color)
            ? { icon: type.default_icon, color: type.default_color }
            : null;
        document.getElementById('iconResetBtn').classList.toggle('d-none', !currentDefault);

        updatePreview();

        document.getElementById('typeForm').action = `/admin/incident-types/${type.id}`;
        document.getElementById('formMethod').value = 'PUT';

        new bootstrap.Modal(document.getElementById('typeModal')).show();
    }

    function resetToDefault() {
        if (!currentDefault) return;
        selectIcon(currentDefault.icon);
        document.getElementById('typeColor').value = currentDefault.color;
        updatePreview();
    }

    function selectIcon(icon) {
        document.querySelectorAll('.icon-choice').forEach(el => el.classList.toggle('is-selected', el.dataset.icon === icon));
        document.getElementById('typeIcon').value = icon;
        // Make sure the selected icon is visible even if it's outside the quick-pick set.
        const match = document.querySelector(`.icon-choice[data-icon="${icon}"]`);
        if (match) match.classList.remove('d-none');
        updatePreview();
    }

    function selectPresetColor(hex) {
        document.getElementById('typeColor').value = hex;
        updatePreview();
    }

    function filterIcons() {
        const q = document.getElementById('iconSearch').value.trim().toLowerCase();

        if (!q) {
            // No search query: restore the default view — quick-pick icons
            // visible, full catalog collapsed back down.
            document.querySelectorAll('.icon-choice:not(.icon-choice--extra)').forEach(el => el.classList.remove('d-none'));
            document.querySelectorAll('.icon-choice--extra').forEach(el => el.classList.add('d-none'));
            return;
        }

        // Dynamic, live filtering across EVERY icon (quick-pick set + full
        // catalog) — previously the quick-pick icons were never touched by
        // the search at all, so they looked "static" while typing.
        document.querySelectorAll('.icon-choice[data-icon]').forEach(el => {
            const matches = el.dataset.icon.toLowerCase().includes(q) || (el.dataset.group || '').toLowerCase().includes(q);
            el.classList.toggle('d-none', !matches);
        });
    }

    document.querySelectorAll('.icon-choice[data-icon]').forEach(label => {
        label.addEventListener('click', () => selectIcon(label.dataset.icon));
    });

    function updatePreview() {
        const icon = document.getElementById('typeIcon').value || 'bi-exclamation-triangle-fill';
        const color = /^#[0-9a-fA-F]{3,8}$/.test(document.getElementById('typeColor').value) ? document.getElementById('typeColor').value : '#64748b';
        const preview = document.getElementById('iconPreview');
        preview.innerHTML = `<i class="bi ${icon} fs-5"></i>`;
        preview.style.background = color + '22';
        preview.style.color = color;
    }
</script>
@endpush
</x-app-layout>
