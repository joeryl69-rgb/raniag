@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
        <div>
            <h1 class="h3 mb-1">Dispatch queue</h1>
            <p class="text-muted mb-0">Sorted by SLA risk, then priority. Target SLA: {{ $slaHours }}h.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card mb-4">
        <div class="card-header fw-semibold">Barangay broadcast (push + SMS)</div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.dispatch.broadcast') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-3">
                    <label class="form-label">Barangay</label>
                    <select name="barangay" class="form-select" required>
                        @foreach (config('raniag.barangays', []) as $b)
                            <option value="{{ $b }}">{{ $b }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" class="form-control" required maxlength="120">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Message</label>
                    <input type="text" name="message" class="form-control" required maxlength="480">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-warning w-100" type="submit">Send</button>
                </div>
            </form>
        </div>
    </div>

    <div class="table-responsive card">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>Tracking</th>
                    <th>Type</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Age</th>
                    <th>SLA</th>
                    <th>Assign</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($queue as $row)
                    @php $inc = $row['incident']; @endphp
                    <tr class="{{ $row['sla_risk'] === 2 ? 'table-danger' : ($row['sla_risk'] === 1 ? 'table-warning' : '') }}">
                        <td>
                            <a href="{{ route('admin.incidents.show', $inc) }}">{{ $inc->tracking_number }}</a>
                            @if ($inc->is_drill)<span class="badge text-bg-secondary">Drill</span>@endif
                        </td>
                        <td>{{ $inc->incidentType?->name }}</td>
                        <td><x-priority-badge :priority="$inc->priority" /></td>
                        <td><x-public.status-badge :status="$inc->status" /></td>
                        <td>{{ $row['age_hours'] }}h</td>
                        <td>
                            @if ($row['sla_risk'] === 2) Breach
                            @elseif ($row['sla_risk'] === 1) At risk
                            @else OK
                            @endif
                        </td>
                        <td style="min-width:220px">
                            <form method="POST" action="{{ route('admin.dispatch.assign', $inc) }}" class="d-flex gap-1">
                                @csrf
                                <select name="agency_id" class="form-select form-select-sm" required>
                                    <option value="">Agency…</option>
                                    @foreach ($agencies as $agency)
                                        <option value="{{ $agency->id }}" @disabled(! $agency->is_available)>
                                            {{ $agency->name }}{{ $agency->is_available ? '' : ' (unavailable)' }}
                                        </option>
                                    @endforeach
                                </select>
                                <button class="btn btn-sm btn-primary" type="submit">Assign</button>
                            </form>
                        </td>
                        <td>
                            <form method="POST" action="{{ route('admin.dispatch.drill', $inc) }}">
                                @csrf
                                <button class="btn btn-sm btn-outline-secondary" type="submit">
                                    {{ $inc->is_drill ? 'Unmark drill' : 'Drill' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">Queue is clear.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
