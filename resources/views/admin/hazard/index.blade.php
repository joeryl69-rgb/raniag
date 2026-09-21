@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <h1 class="h3 mb-1">Hazard zones &amp; evacuation</h1>
    <p class="text-muted mb-4">Manual PAGASA advisory notes/URLs supported. Draw zones as GeoJSON polygons.</p>

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if ($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header fw-semibold">Add hazard zone</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.hazard.zones.store') }}">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label">Type</label>
                            <select name="hazard_zone_type_id" class="form-select" required>
                                @foreach ($zoneTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Name</label>
                            <input name="name" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Barangay</label>
                            <select name="barangay" class="form-select">
                                <option value="">—</option>
                                @foreach ($barangays as $b)<option value="{{ $b }}">{{ $b }}</option>@endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Geometry (GeoJSON Polygon)</label>
                            <textarea name="geometry_json" class="form-control font-monospace small" rows="5" required placeholder='{"type":"Polygon","coordinates":[[[121.32,18.47],[121.33,18.47],[121.33,18.48],[121.32,18.48],[121.32,18.47]]]}'></textarea>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">PAGASA / advisory note</label>
                            <textarea name="advisory_note" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Advisory URL</label>
                            <input name="advisory_url" type="url" class="form-control" placeholder="https://…">
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" checked id="zoneActive">
                            <label class="form-check-label" for="zoneActive">Active</label>
                        </div>
                        <button class="btn btn-primary" type="submit">Save zone</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header fw-semibold">Zones ({{ $zones->count() }})</div>
                <ul class="list-group list-group-flush">
                    @forelse ($zones as $zone)
                        <li class="list-group-item">
                            <div class="fw-semibold">{{ $zone->name }} <span class="badge" style="background:{{ $zone->type?->color }}">{{ $zone->type?->name }}</span></div>
                            <div class="small text-muted">{{ $zone->barangay }} · {{ $zone->is_active ? 'Active' : 'Off' }}</div>
                            @if ($zone->advisory_note)<div class="small mt-1">{{ $zone->advisory_note }}</div>@endif
                        </li>
                    @empty
                        <li class="list-group-item text-muted">No zones yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header fw-semibold">Add evacuation center</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.hazard.centers.store') }}">
                        @csrf
                        <div class="mb-2"><label class="form-label">Name</label><input name="name" class="form-control" required></div>
                        <div class="mb-2">
                            <label class="form-label">Barangay</label>
                            <select name="barangay" class="form-select">
                                <option value="">—</option>
                                @foreach ($barangays as $b)<option value="{{ $b }}">{{ $b }}</option>@endforeach
                            </select>
                        </div>
                        <div class="mb-2"><label class="form-label">Address</label><input name="address" class="form-control"></div>
                        <div class="row g-2 mb-2">
                            <div class="col"><label class="form-label">Lat</label><input name="latitude" class="form-control" required value="{{ $map['default_lat'] }}"></div>
                            <div class="col"><label class="form-label">Lng</label><input name="longitude" class="form-control" required value="{{ $map['default_lng'] }}"></div>
                        </div>
                        <div class="mb-2"><label class="form-label">Capacity</label><input name="capacity" type="number" min="1" class="form-control"></div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="is_open" value="1" checked id="centerOpen">
                            <label class="form-check-label" for="centerOpen">Open</label>
                        </div>
                        <button class="btn btn-primary" type="submit">Save center</button>
                    </form>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header fw-semibold">Register evacuee</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.hazard.evacuees.store') }}">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label">Center</label>
                            <select name="evacuation_center_id" class="form-select" required>
                                @foreach ($centers as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2"><label class="form-label">Full name</label><input name="full_name" class="form-control" required></div>
                        <div class="row g-2 mb-2">
                            <div class="col"><label class="form-label">Age</label><input name="age" type="number" class="form-control"></div>
                            <div class="col"><label class="form-label">Sex</label><input name="sex" class="form-control"></div>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="is_vulnerable" value="1" id="vuln">
                            <label class="form-check-label" for="vuln">Vulnerable</label>
                        </div>
                        <div class="mb-2"><label class="form-label">Vulnerability notes</label><input name="vulnerability_notes" class="form-control"></div>
                        <button class="btn btn-primary" type="submit">Check in</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header fw-semibold">Centers</div>
                <ul class="list-group list-group-flush">
                    @foreach ($centers as $c)
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ $c->name }} <span class="text-muted small">{{ $c->barangay }}</span></span>
                            <span class="badge {{ $c->is_open ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $c->is_open ? 'Open' : 'Closed' }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
