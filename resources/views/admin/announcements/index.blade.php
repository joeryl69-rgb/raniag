<x-app-layout>
    <x-slot name="header">{{ __('Updates & Announcements') }}</x-slot>

    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <p class="small text-muted mb-0">Published items appear in the "Updates &amp; Announcements" section on the public landing page.</p>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#annModal" onclick="openCreateModal()">
            <i class="bi bi-plus-lg me-1"></i>New Announcement
        </button>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="list-group list-group-flush">
            @forelse($announcements as $item)
                <div class="list-group-item py-3">
                    <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                        <div class="d-flex align-items-start gap-2">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-10 text-primary" style="width:38px;height:38px;flex-shrink:0;">
                                <i class="bi {{ $item->icon ?? 'bi-megaphone-fill' }}"></i>
                            </span>
                            <div>
                                <div class="fw-semibold">
                                    {{ $item->title }}
                                    @if($item->badge)<span class="badge bg-light text-dark border ms-1">{{ $item->badge }}</span>@endif
                                </div>
                                <div class="small text-muted">
                                    {{ optional($item->published_at)->format('M d, Y') ?? '—' }}
                                    @if($item->author) · by {{ $item->author->name }} @endif
                                </div>
                                <p class="small mb-0 mt-1 text-muted">{{ Str::limit($item->body, 140) }}</p>
                            </div>
                        </div>
                        <span class="badge {{ $item->is_published ? 'bg-success' : 'bg-secondary' }}">{{ $item->is_published ? 'Published' : 'Hidden' }}</span>
                    </div>
                    <div class="d-flex gap-2 mt-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick='openEditModal(@json($item))'><i class="bi bi-pencil"></i> Edit</button>
                        <form method="POST" action="{{ route('admin.announcements.toggle', $item) }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm {{ $item->is_published ? 'btn-outline-secondary' : 'btn-outline-success' }}">
                                <i class="bi {{ $item->is_published ? 'bi-eye-slash' : 'bi-eye' }}"></i> {{ $item->is_published ? 'Unpublish' : 'Publish' }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.announcements.destroy', $item) }}" class="d-inline" onsubmit="return confirm('Delete this announcement?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="text-center text-muted py-4">No announcements yet. Create one to show it on the landing page.</div>
            @endforelse
        </div>
    </div>
    <div class="mt-3">{{ $announcements->links() }}</div>

    {{-- Create / Edit Modal --}}
    <div class="modal fade" id="annModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="annForm" action="{{ route('admin.announcements.store') }}">
                    @csrf
                    <input type="hidden" name="_method" id="annFormMethod" value="POST">
                    <div class="modal-header">
                        <h6 class="modal-title fw-bold" id="annModalTitle">New Announcement</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Type</label>
                            <select id="annType" class="form-select" onchange="applyAnnType(this.value)">
                                <option value="update" data-badge="Update" data-icon="bi-megaphone-fill">General Update</option>
                                <option value="feature" data-badge="New Feature" data-icon="bi-stars">New Feature</option>
                                <option value="maintenance" data-badge="Maintenance" data-icon="bi-tools">Maintenance / Downtime Notice</option>
                                <option value="alert" data-badge="Safety Alert" data-icon="bi-exclamation-triangle-fill">Safety Alert</option>
                                <option value="reminder" data-badge="Reminder" data-icon="bi-bell-fill"> Reminder</option>
                            </select>
                            <div class="form-text">Sets the small label and icon shown with your announcement — pick whichever best describes it.</div>
                            <input type="hidden" name="badge" id="annBadge">
                            <input type="hidden" name="icon" id="annIcon">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Title</label>
                            <input type="text" name="title" id="annTitle" class="form-control" required maxlength="150" placeholder="A short headline, e.g. Scheduled system maintenance on Sept 5">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Message</label>
                            <textarea name="body" id="annBody" class="form-control" rows="4" maxlength="2000" required placeholder="Write the announcement in plain language — this is what everyone will read on the homepage."></textarea>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_published" id="annPublished" value="1" checked>
                            <label class="form-check-label small" for="annPublished">Published (visible on landing page)</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Announcement</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        const annCreateAction = @json(route('admin.announcements.store'));
        const annTypePresets = {
            update:      { badge: 'Update',        icon: 'bi-megaphone-fill' },
            feature:     { badge: 'New Feature',   icon: 'bi-stars' },
            maintenance: { badge: 'Maintenance',   icon: 'bi-tools' },
            alert:       { badge: 'Safety Alert',  icon: 'bi-exclamation-triangle-fill' },
            reminder:    { badge: 'Reminder',       icon: 'bi-bell-fill' },
        };
        function applyAnnType(type) {
            const preset = annTypePresets[type] || annTypePresets.update;
            document.getElementById('annBadge').value = preset.badge;
            document.getElementById('annIcon').value = preset.icon;
        }
        function guessAnnType(badge, icon) {
            for (const [key, preset] of Object.entries(annTypePresets)) {
                if (preset.icon === icon || preset.badge === badge) return key;
            }
            return 'update';
        }
        function openCreateModal() {
            document.getElementById('annForm').reset();
            document.getElementById('annModalTitle').textContent = 'New Announcement';
            document.getElementById('annForm').action = annCreateAction;
            document.getElementById('annFormMethod').value = 'POST';
            document.getElementById('annPublished').checked = true;
            document.getElementById('annType').value = 'update';
            applyAnnType('update');
        }
        function openEditModal(item) {
            document.getElementById('annModalTitle').textContent = 'Edit Announcement';
            document.getElementById('annTitle').value = item.title;
            document.getElementById('annBody').value = item.body;
            document.getElementById('annPublished').checked = !!item.is_published;
            const type = guessAnnType(item.badge, item.icon);
            document.getElementById('annType').value = type;
            // Preserve the item's actual stored badge/icon rather than
            // overwriting with the preset, in case it doesn't exactly
            // match one (e.g. an older custom value).
            document.getElementById('annBadge').value = item.badge || annTypePresets[type].badge;
            document.getElementById('annIcon').value = item.icon || annTypePresets[type].icon;
            document.getElementById('annForm').action = `/admin/announcements/${item.id}`;
            document.getElementById('annFormMethod').value = 'PUT';
            new bootstrap.Modal(document.getElementById('annModal')).show();
        }
    </script>
    @endpush
</x-app-layout>
