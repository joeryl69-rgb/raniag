<x-app-layout>
    <x-slot name="header">{{ __('Hazard map') }}</x-slot>

@php
    $openRegistry = request('tab') === 'registry' || old('workspace') === 'registry';
    $openCenter = old('workspace') === 'center';
    $checkedIn = $evacuees->whereNull('checked_out_at');
    $advisoryMap = $zones->mapWithKeys(fn ($zone) => [
        $zone->id => [
            'name' => $zone->name,
            'note' => $zone->advisory_note,
            'url' => $zone->advisory_url,
        ],
    ]);
@endphp

<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <p class="small text-muted mb-0">Draw hazard areas and place shelters on one map. Residents see the same shapes on the public Live Map.</p>
    <a href="{{ $publicHazardMapUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
        <i class="bi bi-box-arrow-up-right me-1"></i>Open public Live Map
    </a>
</div>

<div class="row g-2 mb-3">
    <div class="col-6 col-lg-3">
        <div class="hz-stat">
            <div class="value">{{ $zones->count() }}</div>
            <div class="label">Hazard areas</div>
            <x-stat-trend :change="$cardTrends['areas']" note="vs last month" />
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="hz-stat">
            <div class="value">{{ $zones->where('is_active', true)->count() }}</div>
            <div class="label">Shown to the public</div>
            <span class="stat-trend flat">{{ $cardTrends['public']['percent'] }}% of areas</span>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="hz-stat">
            <div class="value">{{ $centers->where('is_open', true)->count() }}<span class="hz-stat-sub">/{{ $centers->count() }}</span></div>
            <div class="label">Shelters open</div>
            <span class="stat-trend flat">{{ $cardTrends['shelters']['percent'] }}% open</span>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="hz-stat">
            <div class="value">{{ $checkedIn->count() }}</div>
            <div class="label">People checked in</div>
            <x-stat-trend :change="$cardTrends['checked_in']" note="vs last month" />
            @if ($cardTrends['outside'])
                <div class="small text-muted mt-1">{{ $cardTrends['outside'] }} from outside Pamplona</div>
            @endif
        </div>
    </div>
</div>

<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item">
        <button class="nav-link {{ $openRegistry ? '' : 'active' }}" data-bs-toggle="tab" data-bs-target="#tab-map" type="button">
            <i class="bi bi-map me-1"></i>Map
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link {{ $openRegistry ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-registry" type="button">
            <i class="bi bi-people me-1"></i>Evacuee registry
            @if ($checkedIn->count())
                <span class="badge bg-primary ms-1">{{ $checkedIn->count() }}</span>
            @endif
        </button>
    </li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade {{ $openRegistry ? '' : 'show active' }}" id="tab-map">
        <div class="hz-workspace">
            <div>
                <div class="hz-toolbar mb-2">
                    <div class="btn-group btn-group-sm" role="group" aria-label="Map tools">
                        <button type="button" class="btn btn-outline-dark" id="tool-polygon"><i class="bi bi-pentagon me-1"></i>Draw area</button>
                        <button type="button" class="btn btn-outline-dark" id="tool-box"><i class="bi bi-square me-1"></i>Draw box</button>
                        <button type="button" class="btn btn-outline-secondary" id="tool-clear">Clear drawing</button>
                    </div>
                    <span class="small text-muted" id="geometry-hint">Choose Draw area or Draw box, then trace the boundary.</span>
                </div>
                <div class="hz-map-wrap">
                    <div id="ops-map"></div>
                    <div class="hz-legend">
                        <span><i class="hz-swatch" id="legend-swatch"></i> Hazard area</span>
                        <span><i class="hz-shelter-key"></i> Evacuation shelter</span>
                    </div>
                </div>
            </div>

            <aside class="hz-panel card border-0 shadow-sm">
                <div class="card-body">
                    <div class="btn-group w-100 mb-3" role="group" aria-label="What to add">
                        <button type="button" class="btn btn-sm {{ $openCenter ? 'btn-outline-primary' : 'btn-primary' }}" id="mode-zone">Hazard area</button>
                        <button type="button" class="btn btn-sm {{ $openCenter ? 'btn-primary' : 'btn-outline-primary' }}" id="mode-center">Shelter pin</button>
                    </div>

                    <form method="POST" action="{{ route('admin.hazard.zones.store') }}" id="hazard-zone-form" class="{{ $openCenter ? 'd-none' : '' }}">
                        @csrf
                        <input type="hidden" name="workspace" value="zone">
                        <input type="hidden" name="geometry_json" id="geometry_json" value="{{ old('workspace') === 'center' ? '' : old('geometry_json') }}">
                        <div class="mb-2">
                            <label class="form-label" for="incident_type_id">Incident type</label>
                            @if ($incidentTypes->isEmpty())
                                <div class="alert alert-warning small mb-0">Add an incident type first. Hazard areas use that same list.</div>
                            @else
                                <select name="incident_type_id" id="incident_type_id" class="form-select" required>
                                    @foreach ($incidentTypes as $type)
                                        @php
                                            $typeColor = \App\Support\IconLibrary::resolveColor($type->color ?: $type->default_color);
                                            $typeIcon = \App\Support\IconLibrary::resolve($type->icon ?: $type->default_icon);
                                        @endphp
                                        <option value="{{ $type->id }}" data-color="{{ $typeColor }}" data-icon="{{ $typeIcon }}" @selected(old('incident_type_id') == $type->id)>{{ $type->name }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text">Same types used when a report is filed. The area color follows the type.</div>
                            @endif
                        </div>
                        <div class="d-flex align-items-center gap-2 mb-3" id="type-preview">
                            <span class="hz-type-chip" id="type-chip"><i class="bi bi-exclamation-triangle-fill" id="type-icon"></i></span>
                            <span class="small text-muted">This color is what residents see.</span>
                        </div>
                        <div class="mb-2">
                            <label class="form-label" for="zone-name">Area name</label>
                            <input name="name" id="zone-name" class="form-control" required maxlength="120" value="{{ old('workspace') === 'zone' ? old('name') : '' }}" placeholder="e.g. Pamplona riverside">
                        </div>
                        <div class="mb-2">
                            <label class="form-label" for="zone-barangay">Barangay</label>
                            <select name="barangay" id="zone-barangay" class="form-select">
                                <option value="">Covers more than one</option>
                                @foreach ($barangays as $b)
                                    <option value="{{ $b }}" @selected(old('workspace') === 'zone' && old('barangay') === $b)>{{ $b }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="zoneActive" @checked(old('workspace') !== 'zone' || old('is_active'))>
                            <label class="form-check-label" for="zoneActive">Show on the public Live Map</label>
                        </div>
                        <button class="btn btn-primary w-100" type="submit" @disabled($incidentTypes->isEmpty())>Save hazard area</button>
                    </form>

                    <form method="POST" action="{{ route('admin.hazard.centers.store') }}" id="center-form" class="{{ $openCenter ? '' : 'd-none' }}">
                        @csrf
                        <input type="hidden" name="workspace" value="center">
                        <p class="small text-muted">Click the map to drop the shelter. Drag the pin if you need to adjust it. The map stays on Pamplona.</p>
                        <div class="mb-2">
                            <label class="form-label" for="center-color">Pin color</label>
                            <input type="color" name="color" id="center-color" class="form-control form-control-color" value="{{ old('color', '#0f766e') }}" title="Color residents see on the live map">
                            <div class="form-text">This is the shelter pin color on the public Live Map.</div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label" for="center-name">Shelter name</label>
                            <input name="name" id="center-name" class="form-control" required maxlength="120" value="{{ old('workspace') === 'center' ? old('name') : '' }}" placeholder="e.g. Tabba elementary school">
                        </div>
                        <div class="mb-2">
                            <label class="form-label" for="center-barangay">Barangay</label>
                            <select name="barangay" id="center-barangay" class="form-select">
                                <option value="">—</option>
                                @foreach ($barangays as $b)
                                    <option value="{{ $b }}" @selected(old('workspace') === 'center' && old('barangay') === $b)>{{ $b }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label" for="center-address">Address</label>
                            <input name="address" id="center-address" class="form-control" maxlength="255" value="{{ old('workspace') === 'center' ? old('address') : '' }}">
                        </div>
                        <div class="mb-2">
                            <label class="form-label" for="center-capacity">Capacity</label>
                            <input name="capacity" id="center-capacity" type="number" min="1" class="form-control" value="{{ old('workspace') === 'center' ? old('capacity') : '' }}" placeholder="People it can hold">
                        </div>
                        <input type="hidden" name="latitude" id="center_latitude" value="{{ old('latitude', $map['default_lat']) }}">
                        <input type="hidden" name="longitude" id="center_longitude" value="{{ old('longitude', $map['default_lng']) }}">
                        <div class="small text-muted mb-2" id="center-coords">Pin not placed yet.</div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="is_open" value="1" id="centerOpen" @checked(old('workspace') !== 'center' || old('is_open'))>
                            <label class="form-check-label" for="centerOpen">Open for evacuees</label>
                        </div>
                        <button class="btn btn-primary w-100" type="submit" id="center-save" disabled>Save shelter</button>
                    </form>
                </div>

                <div class="list-group list-group-flush border-top" id="zone-list">
                    <div class="list-group-item bg-light small fw-semibold text-uppercase text-muted">Saved areas</div>
                    @forelse ($zones as $zone)
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between gap-2">
                                <div class="min-w-0">
                                    <div class="fw-semibold text-truncate">
                                        <span class="hz-dot" style="background:{{ $zone->displayColor() }}"></span>
                                        {{ $zone->name }}
                                    </div>
                                    <div class="small text-muted">{{ $zone->type?->name ?? 'Hazard' }}@if($zone->barangay) · {{ $zone->barangay }}@endif · {{ $zone->is_active ? 'Public' : 'Hidden' }}</div>
                                </div>
                                <div class="d-flex gap-1 flex-shrink-0">
                                    <button type="button" class="btn btn-sm btn-outline-secondary js-advisory" data-id="{{ $zone->id }}">Advisory</button>
                                    <form method="POST" action="{{ route('admin.hazard.zones.destroy', $zone) }}" onsubmit="return confirm('Remove this hazard area?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove"><i class="bi bi-trash3"></i></button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="list-group-item text-muted small">No hazard areas yet.</div>
                    @endforelse
                </div>

                <div class="list-group list-group-flush border-top d-none" id="center-list">
                    <div class="list-group-item bg-light small fw-semibold text-uppercase text-muted">Saved shelters</div>
                    @forelse ($centers as $c)
                        <div class="list-group-item d-flex justify-content-between align-items-center gap-2">
                            <div class="min-w-0">
                                <div class="fw-semibold text-truncate">{{ $c->name }}</div>
                                <div class="small text-muted">
                                    {{ $c->barangay ?: 'No barangay' }}
                                    · {{ $c->is_open ? 'Open' : 'Closed' }}
                                    · {{ $c->checked_in_count }} inside
                                    @if ($c->capacity) / {{ $c->capacity }} @endif
                                </div>
                            </div>
                            <form method="POST" action="{{ route('admin.hazard.centers.update', $c) }}" class="d-flex align-items-center">
                                @csrf
                                @method('PATCH')
                                <input type="color" name="color" class="form-control form-control-color" value="{{ $c->color ?: '#0f766e' }}" onchange="this.form.submit()" title="Pin color" aria-label="Pin color for {{ $c->name }}">
                            </form>
                            <form method="POST" action="{{ route('admin.hazard.centers.destroy', $c) }}" onsubmit="return confirm('Remove this shelter? People checked in here are removed with it.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove"><i class="bi bi-trash3"></i></button>
                            </form>
                        </div>
                    @empty
                        <div class="list-group-item text-muted small">No shelters yet.</div>
                    @endforelse
                </div>
            </aside>
        </div>
    </div>

    <div class="tab-pane fade {{ $openRegistry ? 'show active' : '' }}" id="tab-registry">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <p class="small text-muted mb-0">Who is inside a shelter right now. Check someone in only after their shelter exists on the map.</p>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#checkInModal" @disabled($centers->isEmpty())>
                <i class="bi bi-person-plus me-1"></i>Check in
            </button>
        </div>
        @if ($centers->isEmpty())
            <div class="alert alert-light border">Pin a shelter on the map before registering evacuees.</div>
        @endif

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex flex-wrap gap-2 align-items-center justify-content-between">
                <div class="fw-semibold">{{ $checkedIn->count() }} checked in · {{ $evacuees->count() }} listed</div>
                <div class="d-flex flex-wrap gap-2">
                    <div class="btn-group btn-group-sm" id="evac-origin-filter" role="group" aria-label="Evacuee origin">
                        <button type="button" class="btn btn-primary" data-origin="all">All</button>
                        <button type="button" class="btn btn-outline-secondary" data-origin="inside">Pamplona</button>
                        <button type="button" class="btn btn-outline-secondary" data-origin="outside">Outside</button>
                    </div>
                    <input type="search" class="form-control form-control-sm" id="evacuee-search" placeholder="Search name or shelter" style="max-width: 240px;">
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="evacuee-table">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Shelter</th>
                            <th>From</th>
                            <th>Age / sex</th>
                            <th>Checked in</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($evacuees as $evacuee)
                            <tr data-origin="{{ $evacuee->origin_scope === 'outside' ? 'outside' : 'inside' }}" data-search="{{ strtolower($evacuee->full_name.' '.($evacuee->center?->name ?? '').' '.$evacuee->homeLabel()) }}">
                                <td>
                                    <div class="fw-semibold">{{ $evacuee->full_name }}</div>
                                    @if ($evacuee->isOutsideMunicipality())
                                        <span class="badge text-bg-info">Outside municipality</span>
                                    @endif
                                    @if ($evacuee->is_vulnerable)
                                        <span class="badge text-bg-warning">Needs extra care{{ $evacuee->vulnerability_notes ? ': '.$evacuee->vulnerability_notes : '' }}</span>
                                    @endif
                                </td>
                                <td>{{ $evacuee->center?->name ?? '—' }}</td>
                                <td class="text-muted">{{ $evacuee->homeLabel() }}</td>
                                <td class="text-muted">{{ $evacuee->age ?? '—' }}{{ $evacuee->sex ? ' / '.$evacuee->sex : '' }}</td>
                                <td class="text-muted small">{{ $evacuee->checked_in_at?->format('M j, g:i a') ?? '—' }}</td>
                                <td>
                                    @if ($evacuee->checked_out_at)
                                        <span class="badge text-bg-secondary">Left {{ $evacuee->checked_out_at->format('M j') }}</span>
                                    @else
                                        <span class="badge text-bg-success">Inside</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @unless ($evacuee->checked_out_at)
                                        <form method="POST" action="{{ route('admin.hazard.evacuees.checkout', $evacuee) }}" class="d-inline" onsubmit="return confirm('Check out {{ $evacuee->full_name }}?');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">Check out</button>
                                        </form>
                                    @endunless
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">No one is registered yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="advisoryModal" tabindex="-1" aria-labelledby="advisoryTitle">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" id="advisory-form" action="#">
            @csrf
            @method('PATCH')
            <div class="modal-header">
                <h6 class="modal-title fw-bold" id="advisoryTitle">Advisory</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">Optional note residents read when they tap this area. Leave both fields empty to clear it.</p>
                <div class="mb-3">
                    <label class="form-label" for="advisory_note">What residents should know</label>
                    <textarea name="advisory_note" id="advisory_note" class="form-control" rows="4" maxlength="5000"></textarea>
                </div>
                <div class="mb-0">
                    <label class="form-label" for="advisory_url">Link to the official bulletin</label>
                    <input name="advisory_url" id="advisory_url" type="url" class="form-control" maxlength="500" placeholder="https://">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save advisory</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="checkInModal" tabindex="-1" aria-labelledby="checkInTitle">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="{{ route('admin.hazard.evacuees.store') }}">
            @csrf
            <input type="hidden" name="workspace" value="registry">
            <div class="modal-header">
                <h6 class="modal-title fw-bold" id="checkInTitle">Check in evacuee</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label" for="evacuation_center_id">Shelter</label>
                    <select name="evacuation_center_id" id="evacuation_center_id" class="form-select" required>
                        @foreach ($centers as $c)
                            <option value="{{ $c->id }}" @selected(old('evacuation_center_id') == $c->id)>{{ $c->name }}{{ $c->is_open ? '' : ' (closed)' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="full_name">Full name</label>
                    <input name="full_name" id="full_name" class="form-control" required maxlength="120" value="{{ old('workspace') === 'registry' ? old('full_name') : '' }}">
                </div>
                <div class="mb-3">
                    <span class="form-label d-block">Where they live</span>
                    <div class="btn-group w-100" role="group" aria-label="Evacuee origin">
                        <input class="btn-check" type="radio" name="origin_scope" id="origin-inside" value="inside" @checked(old('origin_scope', 'inside') !== 'outside')>
                        <label class="btn btn-outline-primary" for="origin-inside">Pamplona</label>
                        <input class="btn-check" type="radio" name="origin_scope" id="origin-outside" value="outside" @checked(old('origin_scope') === 'outside')>
                        <label class="btn btn-outline-primary" for="origin-outside">Outside the municipality</label>
                    </div>
                </div>
                <div class="mb-3" id="origin-inside-fields">
                    <label class="form-label" for="evac-barangay">Home barangay</label>
                    <select name="barangay" id="evac-barangay" class="form-select">
                        <option value="">—</option>
                        @foreach ($barangays as $b)
                            <option value="{{ $b }}" @selected(old('workspace') === 'registry' && old('origin_scope', 'inside') !== 'outside' && old('barangay') === $b)>{{ $b }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3 {{ old('origin_scope') === 'outside' ? '' : 'd-none' }}" id="origin-outside-fields">
                    <label class="form-label" for="origin_place">Municipality or city</label>
                    <input name="origin_place" id="origin_place" class="form-control mb-2" maxlength="120" value="{{ old('origin_place') }}" placeholder="e.g. Abulug">
                    <label class="form-label" for="evac-barangay-out">Barangay or sitio there</label>
                    <input id="evac-barangay-out" class="form-control" maxlength="100" value="{{ old('origin_scope') === 'outside' ? old('barangay') : '' }}" placeholder="Optional">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col">
                        <label class="form-label" for="age">Age</label>
                        <input name="age" id="age" type="number" min="0" max="120" class="form-control" value="{{ old('workspace') === 'registry' ? old('age') : '' }}">
                    </div>
                    <div class="col">
                        <label class="form-label" for="sex">Sex</label>
                        <select name="sex" id="sex" class="form-select">
                            <option value="">—</option>
                            <option value="Female" @selected(old('sex') === 'Female')>Female</option>
                            <option value="Male" @selected(old('sex') === 'Male')>Male</option>
                        </select>
                    </div>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="is_vulnerable" value="1" id="vuln" @checked(old('workspace') === 'registry' && old('is_vulnerable'))>
                    <label class="form-check-label" for="vuln">Needs extra care (PWD, elderly, pregnant, infant)</label>
                </div>
                <div>
                    <label class="form-label" for="vulnerability_notes">Care notes</label>
                    <input name="vulnerability_notes" id="vulnerability_notes" class="form-control" maxlength="255" value="{{ old('workspace') === 'registry' ? old('vulnerability_notes') : '' }}">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Check in</button>
            </div>
        </form>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css">
<style>
.hz-stat { border: 1px solid #e5e7eb; border-radius: 12px; padding: 12px 14px; background: #fff; }
.hz-stat .value { font-size: 1.35rem; font-weight: 750; line-height: 1; }
.hz-stat-sub { font-size: .85rem; font-weight: 600; color: #6b7280; }
.hz-stat .label { font-size: .72rem; color: #6b7280; text-transform: uppercase; letter-spacing: .04em; margin-top: 4px; }
.stat-trend { display:inline-flex; align-items:center; gap:2px; margin-top:6px; font-size:.75rem; font-weight:700; }
.stat-trend.up { color:#b45309; }
.stat-trend.down { color:#0f766e; }
.stat-trend.flat { color:#6b7280; }
.hz-workspace { display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: 16px; align-items: start; }
.hz-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; }
.hz-map-wrap { position: relative; height: calc(100vh - 280px); min-height: 520px; border-radius: 12px; overflow: hidden; border: 1px solid #e5e7eb; background: #e8eef2; }
#ops-map { height: 100%; width: 100%; }
.hz-legend { position: absolute; left: 12px; bottom: 12px; z-index: 500; background: #fff; border-radius: 8px; padding: 6px 10px; display: flex; gap: 12px; font-size: .75rem; box-shadow: 0 4px 14px rgba(15,23,42,.12); }
.hz-legend span { display: inline-flex; align-items: center; gap: 6px; }
.hz-swatch { width: 14px; height: 10px; border-radius: 2px; background: #b45309; display: inline-block; }
.hz-shelter-key { width: 12px; height: 12px; border-radius: 50%; background: #0f766e; display: inline-block; box-shadow: 0 0 0 2px #fff, 0 0 0 3px #0f766e; }
.hz-panel { max-height: calc(100vh - 230px); overflow: auto; }
.hz-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 4px; }
.hz-type-chip { width: 32px; height: 32px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; background: #f1f5f9; }
.leaflet-draw-tooltip { font-size: 12px; }
@media (max-width: 991.98px) {
    .hz-workspace { grid-template-columns: 1fr; }
    .hz-map-wrap { height: 420px; min-height: 420px; }
    .hz-panel { max-height: none; }
}
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js"></script>
<script src="{{ asset('js/raniag-mapbox.js') }}?v={{ @filemtime(public_path('js/raniag-mapbox.js')) }}"></script>
<script>
(function () {
    const mapCfg = @json($map);
    const existingZones = @json($mapZonesJson);
    const existingCenters = @json($mapCentersJson);
    const advisoryTemplate = @json(route('admin.hazard.zones.update', ['zone' => '__ID__']));
    const advisories = @json($advisoryMap);
    const startCenter = @json($openCenter);
    const hadPin = @json(old('workspace') === 'center');

    const typeSelect = document.getElementById('incident_type_id');
    const geometryInput = document.getElementById('geometry_json');
    const geometryHint = document.getElementById('geometry-hint');
    const legendSwatch = document.getElementById('legend-swatch');
    const typeChip = document.getElementById('type-chip');
    const typeIcon = document.getElementById('type-icon');
    const zoneForm = document.getElementById('hazard-zone-form');
    const centerForm = document.getElementById('center-form');
    const zoneList = document.getElementById('zone-list');
    const centerList = document.getElementById('center-list');
    const latInput = document.getElementById('center_latitude');
    const lngInput = document.getElementById('center_longitude');
    const coordsLabel = document.getElementById('center-coords');
    const centerSave = document.getElementById('center-save');

    function currentColor() {
        return typeSelect?.selectedOptions?.[0]?.dataset?.color || '#b45309';
    }

    function syncTypePreview() {
        const opt = typeSelect?.selectedOptions?.[0];
        const color = opt?.dataset?.color || '#64748b';
        const icon = opt?.dataset?.icon || 'bi-exclamation-triangle-fill';
        if (legendSwatch) legendSwatch.style.background = color;
        if (typeChip) {
            typeChip.style.background = color + '22';
            typeChip.style.color = color;
        }
        if (typeIcon) typeIcon.className = 'bi ' + icon;
        if (drawnLayer && drawnLayer.setStyle) styleDrawn();
    }

    const colorInput = document.getElementById('center-color');
    function pinColor() {
        const value = colorInput?.value || '#0f766e';
        return /^#[0-9A-Fa-f]{6}$/.test(value) ? value : '#0f766e';
    }

    function shelterIcon(open, draft, color) {
        const pin = open ? (color || '#0f766e') : '#64748b';
        return L.divIcon({
            className: 'rg-evac-marker' + (draft ? ' rg-shelter-draft' : ''),
            html: '<div class="rg-evac-pin' + (open ? '' : ' is-closed') + '" style="--pin:' + pin + '" aria-hidden="true"><svg viewBox="0 0 16 16" width="15" height="15"><path fill="#fff" d="M8.354 1.146a.5.5 0 0 0-.708 0l-6 6A.5.5 0 0 0 1.5 7.5v7a.5.5 0 0 0 .5.5h4.5a.5.5 0 0 0 .5-.5v-4h2v4a.5.5 0 0 0 .5.5H14a.5.5 0 0 0 .5-.5v-7a.5.5 0 0 0-.146-.354z"/></svg></div>',
            iconSize: [34, 42],
            iconAnchor: [17, 40],
        });
    }

    const map = L.map('ops-map', { zoomControl: false, maxZoom: 17 }).setView(
        [mapCfg.default_lat, mapCfg.default_lng],
        mapCfg.default_zoom || 13
    );
    L.control.zoom({ position: 'topright' }).addTo(map);
    if (window.RANIAG_Mapbox) {
        window.RANIAG_Mapbox.addBasemap(map, mapCfg);
    } else {
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OSM' }).addTo(map);
    }

    const zoneLayer = L.featureGroup().addTo(map);
    existingZones.forEach((z) => {
        try {
            const layer = L.geoJSON(z.geometry, {
                style: {
                    color: z.color || '#b45309',
                    weight: z.active ? 2.5 : 1.5,
                    fillColor: z.color || '#b45309',
                    fillOpacity: z.active ? 0.38 : 0.12,
                    dashArray: z.active ? null : '5 4',
                    className: z.active ? 'rg-hazard-poly' : '',
                },
            }).bindTooltip(z.name);
            zoneLayer.addLayer(layer);
        } catch (e) {}
    });

    existingCenters.forEach((c) => {
        L.marker([c.lat, c.lng], { icon: shelterIcon(c.open, false, c.color), interactive: true })
            .addTo(map)
            .bindTooltip(c.name + (c.open ? '' : ' (closed)'));
    });

    let drawnLayer = null;
    const drawnItems = new L.FeatureGroup().addTo(map);
    const shapeOptions = () => ({ color: currentColor(), fillColor: currentColor(), weight: 2.5, fillOpacity: 0.42 });
    let polygonDrawer = new L.Draw.Polygon(map, { allowIntersection: false, showArea: true, shapeOptions: shapeOptions() });
    let boxDrawer = new L.Draw.Rectangle(map, { shapeOptions: shapeOptions() });

    function refreshDrawers() {
        polygonDrawer.disable();
        boxDrawer.disable();
        polygonDrawer = new L.Draw.Polygon(map, { allowIntersection: false, showArea: true, shapeOptions: shapeOptions() });
        boxDrawer = new L.Draw.Rectangle(map, { shapeOptions: shapeOptions() });
    }

    function styleDrawn() {
        if (drawnLayer && drawnLayer.setStyle) drawnLayer.setStyle(shapeOptions());
    }

    function capture(layer) {
        const geo = layer.toGeoJSON();
        geometryInput.value = JSON.stringify(geo.geometry || geo);
        if (geometryHint) geometryHint.textContent = 'Boundary captured. Save the area, or clear it and draw again.';
    }

    map.on(L.Draw.Event.CREATED, function (e) {
        drawnItems.clearLayers();
        drawnLayer = e.layer;
        drawnItems.addLayer(drawnLayer);
        styleDrawn();
        capture(drawnLayer);
        polygonDrawer.disable();
        boxDrawer.disable();
    });

    document.getElementById('tool-polygon')?.addEventListener('click', () => {
        setMode('zone');
        refreshDrawers();
        polygonDrawer.enable();
        if (geometryHint) geometryHint.textContent = 'Click to add corners. Click the first corner again to close the area.';
    });
    document.getElementById('tool-box')?.addEventListener('click', () => {
        setMode('zone');
        refreshDrawers();
        boxDrawer.enable();
        if (geometryHint) geometryHint.textContent = 'Click one corner, then the opposite corner.';
    });
    document.getElementById('tool-clear')?.addEventListener('click', () => {
        polygonDrawer.disable();
        boxDrawer.disable();
        drawnItems.clearLayers();
        drawnLayer = null;
        geometryInput.value = '';
        if (geometryHint) geometryHint.textContent = 'Drawing cleared. Trace the boundary again.';
    });

    typeSelect?.addEventListener('change', () => {
        syncTypePreview();
        refreshDrawers();
    });
    syncTypePreview();

    if (geometryInput.value) {
        try {
            const geo = JSON.parse(geometryInput.value);
            const restored = L.geoJSON(geo);
            restored.eachLayer((layer) => {
                drawnLayer = layer;
                drawnItems.addLayer(layer);
                styleDrawn();
            });
        } catch (e) {}
    }

    let draftMarker = null;
    let pinPlaced = false;
    function placeDraft(lat, lng) {
        latInput.value = Number(lat).toFixed(7);
        lngInput.value = Number(lng).toFixed(7);
        if (!draftMarker) {
            draftMarker = L.marker([lat, lng], { icon: shelterIcon(true, true, pinColor()), draggable: true, zIndexOffset: 1000 }).addTo(map);
            draftMarker.on('dragend', () => {
                const p = draftMarker.getLatLng();
                placeDraft(p.lat, p.lng);
            });
        } else {
            draftMarker.setLatLng([lat, lng]);
        }
        pinPlaced = true;
        centerSave.disabled = false;
        coordsLabel.textContent = 'Pin placed at ' + Number(lat).toFixed(5) + ', ' + Number(lng).toFixed(5);
    }

    colorInput?.addEventListener('input', () => {
        if (draftMarker) draftMarker.setIcon(shelterIcon(true, true, pinColor()));
    });

    map.on('click', (e) => {
        if (centerForm.classList.contains('d-none')) return;
        if (polygonDrawer._enabled || boxDrawer._enabled) return;
        placeDraft(e.latlng.lat, e.latlng.lng);
    });

    function setMode(mode) {
        const center = mode === 'center';
        zoneForm.classList.toggle('d-none', center);
        zoneList.classList.toggle('d-none', center);
        centerForm.classList.toggle('d-none', !center);
        centerList.classList.toggle('d-none', !center);
        document.getElementById('mode-zone').className = 'btn btn-sm ' + (center ? 'btn-outline-primary' : 'btn-primary');
        document.getElementById('mode-center').className = 'btn btn-sm ' + (center ? 'btn-primary' : 'btn-outline-primary');
        if (center) {
            polygonDrawer.disable();
            boxDrawer.disable();
            if (geometryHint) geometryHint.textContent = 'Click the map to drop the shelter pin.';
        } else if (geometryHint && !geometryInput.value) {
            geometryHint.textContent = 'Choose Draw area or Draw box, then trace the boundary.';
        }
        setTimeout(() => map.invalidateSize(), 50);
    }

    document.getElementById('mode-zone')?.addEventListener('click', () => setMode('zone'));
    document.getElementById('mode-center')?.addEventListener('click', () => setMode('center'));
    if (startCenter) setMode('center');
    if (hadPin && latInput.value && lngInput.value) placeDraft(parseFloat(latInput.value), parseFloat(lngInput.value));

    zoneForm?.addEventListener('submit', (e) => {
        if (!geometryInput.value) {
            e.preventDefault();
            alert('Draw the hazard boundary on the map before saving.');
        }
    });

    const advisoryModal = document.getElementById('advisoryModal');
    document.querySelectorAll('.js-advisory').forEach((btn) => {
        btn.addEventListener('click', () => {
            const row = advisories[btn.dataset.id] || {};
            document.getElementById('advisory-form').action = advisoryTemplate.replace('__ID__', btn.dataset.id);
            document.getElementById('advisoryTitle').textContent = 'Advisory · ' + (row.name || 'Hazard area');
            document.getElementById('advisory_note').value = row.note || '';
            document.getElementById('advisory_url').value = row.url || '';
            bootstrap.Modal.getOrCreateInstance(advisoryModal).show();
        });
    });

    let evacOrigin = 'all';
    function applyEvacueeFilter() {
        const q = (document.getElementById('evacuee-search')?.value || '').trim().toLowerCase();
        document.querySelectorAll('#evacuee-table tbody tr[data-search]').forEach((row) => {
            const originOk = evacOrigin === 'all' || row.dataset.origin === evacOrigin;
            const textOk = q === '' || row.dataset.search.includes(q);
            row.classList.toggle('d-none', !(originOk && textOk));
        });
    }
    document.getElementById('evacuee-search')?.addEventListener('input', applyEvacueeFilter);
    document.querySelectorAll('#evac-origin-filter button').forEach((button) => {
        button.addEventListener('click', () => {
            evacOrigin = button.dataset.origin || 'all';
            document.querySelectorAll('#evac-origin-filter button').forEach((item) => {
                item.classList.toggle('btn-primary', item === button);
                item.classList.toggle('btn-outline-secondary', item !== button);
            });
            applyEvacueeFilter();
        });
    });

    const insideFields = document.getElementById('origin-inside-fields');
    const outsideFields = document.getElementById('origin-outside-fields');
    const insideBarangay = document.getElementById('evac-barangay');
    const outsideBarangay = document.getElementById('evac-barangay-out');
    const originPlace = document.getElementById('origin_place');
    function syncOrigin() {
        const outside = document.getElementById('origin-outside')?.checked;
        insideFields?.classList.toggle('d-none', outside);
        outsideFields?.classList.toggle('d-none', !outside);
        if (insideBarangay) insideBarangay.disabled = !!outside;
        if (outsideBarangay) {
            outsideBarangay.disabled = !outside;
            outsideBarangay.name = outside ? 'barangay' : '';
        }
        if (originPlace) originPlace.disabled = !outside;
    }
    document.querySelectorAll('input[name="origin_scope"]').forEach((input) => input.addEventListener('change', syncOrigin));
    syncOrigin();

    function fitMap() {
        map.invalidateSize();
        const hasZones = zoneLayer.getLayers().length > 0;
        if (!hasZones && existingCenters.length <= 1) {
            const spot = existingCenters[0];
            map.setView(
                spot ? [spot.lat, spot.lng] : [mapCfg.default_lat, mapCfg.default_lng],
                mapCfg.default_zoom || 13
            );
            return;
        }
        const bounds = L.latLngBounds([]);
        if (hasZones) bounds.extend(zoneLayer.getBounds());
        existingCenters.forEach((c) => bounds.extend([c.lat, c.lng]));
        if (bounds.isValid()) map.fitBounds(bounds.pad(0.2), { maxZoom: 14, animate: false });
    }

    document.querySelector('[data-bs-target="#tab-map"]')?.addEventListener('shown.bs.tab', fitMap);
    if (document.getElementById('tab-map').classList.contains('active')) {
        setTimeout(fitMap, 200);
    }

    @if ($openRegistry && $errors->any())
        bootstrap.Modal.getOrCreateInstance(document.getElementById('checkInModal')).show();
    @endif

})();
</script>
@endpush
</x-app-layout>
