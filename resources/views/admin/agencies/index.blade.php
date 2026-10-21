@php
    $activeDirectory = request('directory') === 'personnel' ? 'personnel' : 'agencies';
@endphp

<x-app-layout>
    <x-slot name="header">
        {{ $activeDirectory === 'personnel' ? __('Personnel directory') : __('Agency directory') }}
    </x-slot>
    <x-workspace-intro
        eyebrow="COORDINATION / RESPONSE NETWORK"
        :title="$activeDirectory === 'personnel' ? 'Personnel directory' : 'Agency directory'"
        :description="$activeDirectory === 'personnel' ? 'Manage response staff accounts, role titles, and team assignments.' : 'Manage organizations and the primary accounts used to coordinate incident response.'"
        :icon="$activeDirectory === 'personnel' ? 'bi-person-badge' : 'bi-building'"
        variant="agency-directory"
    >
        <div class="d-flex flex-wrap gap-2">
            @if ($activeDirectory === 'personnel')
                <a href="{{ route('admin.personnel_roles.index') }}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-diagram-3 me-1"></i>Manage roles
                </a>
            @endif
            <a href="{{ route('admin.agencies.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Add {{ $activeDirectory === 'personnel' ? 'personnel' : 'agency' }}
            </a>
        </div>
    </x-workspace-intro>

    <x-kpi-strip :items="[
        [
            'label' => $activeDirectory === 'personnel' ? 'Personnel accounts' : 'Agency accounts',
            'value' => $activeDirectory === 'personnel' ? $personnelAccounts->total() : $agencies->total(),
            'icon' => $activeDirectory === 'personnel' ? 'bi-person-badge' : 'bi-building',
            'tone' => $activeDirectory === 'personnel' ? 'success' : 'primary',
            'sub' => 'Matching this directory',
        ],
    ]" />

    <div class="rg-directory-workspace">
        @if ($activeDirectory === 'agencies')
            <div class="row">
                <div class="col-12">
            <div class="card raniag-card rg-table-desk rg-filter-host shadow-sm border-0">
                <div class="rg-table-desk-head">
                    <div>
                        <h2>Agency directory <span>{{ $agencies->total() }} accounts</span></h2>
                        <p>Organization profiles and their primary login accounts.</p>
                    </div>
                    <div class="d-flex align-items-center gap-2 rg-register-actions">
                        <i class="bi bi-building text-primary fs-5" aria-hidden="true"></i>
                    </div>
                </div>
                <div class="p-3 border-bottom bg-light-subtle">
                    <x-filters.toolbar
                        search-placeholder="Search name, code, email"
                        search-name="agency_q"
                        date-from-name="agency_date_from"
                        date-to-name="agency_date_to"
                        :action="route('admin.agencies.index', ['directory' => 'agencies'])"
                        :clear-url="route('admin.agencies.index', ['directory' => 'agencies'])"
                        :compact="true"
                    />
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive rg-data-table-wrap" data-live-refresh data-live-refresh-target="#rg-agencies-tbody" data-live-refresh-interval="4000">
                        <table class="table table-hover table-striped mb-0 align-middle rg-data-table">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th class="px-4 py-3">Code</th>
                                    <th class="py-3"><x-table-sort-link label="Agency" column="name" route="admin.agencies.index" sort-name="agency_sort" direction-name="agency_direction" default-sort="created_at" default-direction="asc" /></th>
                                    <th class="py-3">Phone</th>
                                    <th class="py-3">Email Address</th>
                                    <th class="py-3">Login Name</th>
                                    <th class="py-3">Status</th>
                                    <th class="px-4 py-3 text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="rg-agencies-tbody">
                                @forelse ($agencies as $agency)
                                    @php
                                        $primaryUser = $agency->users->first();
                                    @endphp
                                    <tr>
                                        <td class="px-4 py-3"><span class="badge bg-dark px-2 py-1 fs-6">{{ $agency->code }}</span></td>
                                        <td class="py-3 text-dark fw-semibold">{{ $agency->name }}</td>
                                        <td class="py-3">{{ $agency->phone ?? 'N/A' }}</td>
                                        <td class="py-3">{{ $agency->email ?? 'N/A' }}</td>
                                        <td class="py-3">
                                            @if ($primaryUser)
                                                <div class="small fw-semibold text-dark">{{ $primaryUser->name }}</div>
                                            @else
                                                <span class="text-danger small"><i class="bi bi-exclamation-triangle-fill me-1"></i>No Login Account</span>
                                            @endif
                                        </td>
                                        <td class="py-3">
                                            @if ($agency->is_active)
                                                <span class="badge bg-success px-2 py-1"><i class="bi bi-check-circle me-1"></i>Active</span>
                                            @else
                                                <span class="badge bg-secondary px-2 py-1"><i class="bi bi-slash-circle me-1"></i>Inactive</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-end">
                                            <a href="{{ route('admin.agencies.edit', $agency->id) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil me-1"></i>Edit
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteAgency{{ $agency->id }}">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                            <div class="modal fade" id="deleteAgency{{ $agency->id }}" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Delete Agency Account</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body text-start">
                                                            Permanently delete <strong>{{ $agency->name }}</strong> and its login account?
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <form action="{{ route('admin.agencies.destroy', $agency->id) }}" method="POST">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-danger">Delete</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted">
                                            <i class="bi bi-building display-4"></i>
                                            <p class="mt-2 mb-0">No agencies created yet.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($agencies->hasPages())
                    <div class="card-footer bg-white border-0 py-3">
                        {!! $agencies->links('pagination::bootstrap-5') !!}
                    </div>
                @endif
            </div>
            </div>
        @endif

        @if ($activeDirectory === 'personnel')
            <div class="row">
                <div class="col-12">
            <div class="card raniag-card rg-table-desk rg-filter-host shadow-sm border-0 mb-4">
                <div class="rg-table-desk-head">
                    <div>
                        <h2>Personnel directory <span>{{ $personnelAccounts->total() }} accounts</span></h2>
                        <p>Internal response staff, roles, and team assignments.</p>
                    </div>
                    <div class="d-flex align-items-center gap-2 rg-register-actions">
                        <i class="bi bi-person-badge text-primary fs-5" aria-hidden="true"></i>
                    </div>
                </div>
                <div class="p-3 border-bottom bg-light-subtle">
                    <x-filters.toolbar
                        search-placeholder="Search name, email, role, team"
                        search-name="personnel_q"
                        date-from-name="personnel_date_from"
                        date-to-name="personnel_date_to"
                        :action="route('admin.agencies.index', ['directory' => 'personnel'])"
                        :clear-url="route('admin.agencies.index', ['directory' => 'personnel'])"
                        :compact="true"
                    />
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive rg-data-table-wrap" data-live-refresh data-live-refresh-target="#rg-personnel-tbody" data-live-refresh-interval="4000">
                        <table class="table table-hover table-striped mb-0 align-middle rg-data-table">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th class="px-4 py-3"><x-table-sort-link label="Name" column="name" route="admin.agencies.index" sort-name="personnel_sort" direction-name="personnel_direction" default-sort="created_at" default-direction="asc" /></th>
                                    <th class="py-3">Email</th>
                                    <th class="py-3">Role title</th>
                                    <th class="py-3">Team assignment</th>
                                    <th class="py-3">Phone</th>
                                    <th class="py-3">Status</th>
                                    <th class="px-4 py-3 text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="rg-personnel-tbody">
                                @forelse ($personnelAccounts as $personnel)
                                    <tr>
                                        <td class="px-4 py-3 text-dark fw-semibold">{{ $personnel->name }}</td>
                                        <td class="py-3">{{ $personnel->email }}</td>
                                        <td class="py-3">{{ $personnel->role_title ?? 'N/A' }}</td>
                                        <td class="py-3">{{ $personnel->team_assignment ?? 'N/A' }}</td>
                                        <td class="py-3">{{ $personnel->phone ?? 'N/A' }}</td>
                                        <td class="py-3">
                                            @if ($personnel->is_active)
                                                <span class="badge bg-success px-2 py-1"><i class="bi bi-check-circle me-1"></i>Active</span>
                                            @else
                                                <span class="badge bg-secondary px-2 py-1"><i class="bi bi-slash-circle me-1"></i>Inactive</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-end">
                                            <a href="{{ route('admin.personnel.edit', $personnel->id) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil me-1"></i>Edit
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deletePersonnel{{ $personnel->id }}">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                            <div class="modal fade" id="deletePersonnel{{ $personnel->id }}" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Delete Personnel Account</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body text-start">
                                                            Permanently delete <strong>{{ $personnel->name }}</strong>'s account?
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <form action="{{ route('admin.personnel.destroy', $personnel->id) }}" method="POST">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-danger">Delete</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="bi bi-person-slash display-4"></i>
                                            <p class="mt-2 mb-0">No personnel accounts created yet.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($personnelAccounts->hasPages())
                    <div class="card-footer bg-white border-0 py-3">
                        {!! $personnelAccounts->links('pagination::bootstrap-5') !!}
                    </div>
                @endif
            </div>
        </div>
            </div>
        @endif
    </div>
</x-app-layout>
