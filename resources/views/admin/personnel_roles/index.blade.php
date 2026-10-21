<x-app-layout>
    <x-slot name="header">
        {{ __('Personnel Roles') }}
    </x-slot>

    <x-workspace-intro
        eyebrow="COORDINATION / ACCOUNT CONFIGURATION"
        title="Personnel roles"
        description="Manage the role titles offered when creating and updating personnel accounts."
        icon="bi-diagram-3"
        variant="agency-directory"
    >
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.agencies.index', ['directory' => 'personnel']) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Personnel directory
            </a>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#roleModal" onclick="openCreateModal()">
                <i class="bi bi-plus-lg me-1"></i>Add role
            </button>
        </div>
    </x-workspace-intro>

<x-kpi-strip :items="[
    ['label' => 'Role titles', 'value' => $roles->count(), 'icon' => 'bi-diagram-3', 'tone' => 'primary', 'sub' => 'Offered on personnel accounts'],
    ['label' => 'Active titles', 'value' => $roles->where('is_active', true)->count(), 'icon' => 'bi-check-circle', 'tone' => 'success'],
    ['label' => 'Assigned to someone', 'value' => $roles->filter(fn ($role) => (int) $role->personnel_count > 0)->count(), 'icon' => 'bi-person-check', 'tone' => 'warning'],
]" />

<section class="rg-table-desk" aria-labelledby="rg-role-register-heading">
    <header class="rg-table-desk-head">
        <div>
            <h2 id="rg-role-register-heading">Role title register <span>{{ $roles->count() }} titles</span></h2>
            <p>Choose active titles for agency personnel accounts.</p>
        </div>
        <i class="bi bi-diagram-3 text-primary fs-5" aria-hidden="true"></i>
    </header>
    <div class="table-responsive rg-data-table-wrap">
        <table class="table table-hover align-middle mb-0 rg-data-table">
            <thead class="table-light">
                <tr>
                    <th><x-table-sort-link label="Role title" column="title" route="admin.personnel_roles.index" default-sort="sort_order" default-direction="asc" /></th>
                    <th class="text-center">Personnel Using It</th>
                    <th class="text-center">Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($roles as $role)
                    <tr>
                        <td class="fw-semibold">{{ $role->title }}</td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border">{{ $role->personnel_count }}</span>
                        </td>
                        <td class="text-center">
                            <form method="POST" action="{{ route('admin.personnel_roles.toggle', $role) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm {{ $role->is_active ? 'btn-outline-success' : 'btn-outline-secondary' }}">
                                    {{ $role->is_active ? 'Active' : 'Inactive' }}
                                </button>
                            </form>
                        </td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                data-role-edit="{{ json_encode($role->only(['id', 'title', 'sort_order'])) }}">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form method="POST" action="{{ route('admin.personnel_roles.destroy', $role) }}" class="d-inline" onsubmit="return confirm('Delete &quot;{{ $role->title }}&quot;? This cannot be undone.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" {{ $role->personnel_count > 0 ? 'disabled title="Cannot delete a role already assigned to personnel accounts"' : '' }}>
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">No personnel roles yet. Add one to get started.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

{{-- Create / Edit Modal (shared) --}}
<div class="modal fade" id="roleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="roleForm">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">
                <div class="modal-header">
                    <h6 class="modal-title fw-bold" id="roleModalTitle">Add Personnel Role</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Role Title</label>
                        <input type="text" name="title" id="roleTitle" class="form-control" required maxlength="150" placeholder="e.g. Logistics Officer">
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-semibold">Sort Order <span class="text-muted fw-normal">(optional)</span></label>
                        <input type="number" name="sort_order" id="roleSortOrder" class="form-control" min="0" max="999" placeholder="0">
                        <div class="form-text">Lower numbers appear first in the dropdown.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Role</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const roleModalEl = document.getElementById('roleModal');
    const roleModal = new bootstrap.Modal(roleModalEl);

    function openCreateModal() {
        document.getElementById('roleModalTitle').textContent = 'Add Personnel Role';
        document.getElementById('roleForm').action = @json(route('admin.personnel_roles.store'));
        document.getElementById('formMethod').value = 'POST';
        document.getElementById('roleTitle').value = '';
        document.getElementById('roleSortOrder').value = '';
    }

    function openEditModal(role) {
        document.getElementById('roleModalTitle').textContent = 'Edit Personnel Role';
        document.getElementById('roleForm').action = '/admin/personnel-roles/' + role.id;
        document.getElementById('formMethod').value = 'PUT';
        document.getElementById('roleTitle').value = role.title;
        document.getElementById('roleSortOrder').value = role.sort_order ?? '';
        roleModal.show();
    }

    document.addEventListener('click', function (event) {
        const button = event.target.closest('[data-role-edit]');
        if (!button) return;
        openEditModal(JSON.parse(button.getAttribute('data-role-edit')));
    });
</script>
@endpush
</x-app-layout>
