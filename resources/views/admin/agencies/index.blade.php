<x-app-layout>
    <x-slot name="header">
        {{ __('Government Agencies Management') }}
    </x-slot>

    <div class="row">
        <div class="col-12">
            <div class="card raniag-card shadow-sm border-0 mb-4">
                <div class="card-body py-3 bg-white d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-people-fill me-2 text-primary"></i>Manage Accounts</h5>
                        <p class="text-muted small mb-0">Create and manage agency accounts as well as internal personnel response accounts.</p>
                    </div>
                    <a href="{{ route('admin.agencies.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i>Add New Account
                    </a>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card raniag-card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 align-middle">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th class="px-4 py-3">Code</th>
                                    <th class="py-3">Name</th>
                                    <th class="py-3">Phone</th>
                                    <th class="py-3">Email Address</th>
                                    <th class="py-3">Officer Login</th>
                                    <th class="py-3">Status</th>
                                    <th class="px-4 py-3 text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
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
                                                <div class="text-muted small">{{ $primaryUser->email }}</div>
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

        <div class="col-12">
            <div class="card raniag-card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-person-badge me-2 text-primary"></i>Personnel Accounts</h5>
                        <p class="text-muted small mb-0">View active internal personnel accounts and their team assignments.</p>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 align-middle">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th class="px-4 py-3">Name</th>
                                    <th class="py-3">Email</th>
                                    <th class="py-3">Role Title</th>
                                    <th class="py-3">Team Assignment</th>
                                    <th class="py-3">Phone</th>
                                    <th class="py-3">Status</th>
                                    <th class="px-4 py-3 text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
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
</x-app-layout>
