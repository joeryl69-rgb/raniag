<x-app-layout>
    <x-slot name="header">
        {{ __('Personnel Roles') }}
    </x-slot>

<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <p class="small text-muted mb-0">Role titles offered when registering or editing a Personnel account. Manage them here instead of a fixed list.</p>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.agencies.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back to Agencies &amp; Personnel
        </a>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#roleModal" onclick="openCreateModal()">
            <i class="bi bi-plus-lg me-1"></i>Add Role
        </button>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Role Title</th>
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
                                onclick='openEditModal(@json($role))'>
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
</div>

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
</script>
@endpush
</x-app-layout>
