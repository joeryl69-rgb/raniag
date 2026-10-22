<x-app-layout>
    <x-slot name="header">{{ __('Updates and announcements') }}</x-slot>
    <x-workspace-intro
        eyebrow="OVERSIGHT / PUBLIC INFORMATION"
        title="Updates & announcements"
        description="Publish timely notices and safety updates to the RANIAG landing page."
        icon="bi-megaphone"
        variant="announcement-studio"
    >
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#annModal" onclick="openCreateModal()"><i class="bi bi-plus-lg me-1"></i>New update</button>
    </x-workspace-intro>

    <x-kpi-strip :items="[
        ['label' => 'Announcements', 'value' => $announcements->total(), 'icon' => 'bi-megaphone', 'tone' => 'primary', 'sub' => 'Matching the filters'],
    ]" />

    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <p class="small text-muted mb-0">Published items appear in the Updates and announcements section on the public landing page.</p>
    </div>

    <div class="card rg-filter-host border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <x-filters.toolbar search-placeholder="Search title or message" :action="route('admin.announcements.index')" :compact="true">
                <x-announcement-filter-fields :include-status="true" />
            </x-filters.toolbar>
        </div>
    </div>

    <div class="rg-announcement-surface" data-live-refresh data-live-refresh-target="#rg-announcements-list" data-live-refresh-interval="4000">
        <div class="list-group list-group-flush rg-announcement-list" id="rg-announcements-list">
            @forelse($announcements as $item)
                <article class="list-group-item rg-announcement-item">
                    <div class="rg-announcement-content">
                        <div class="d-flex align-items-start gap-3">
                            <span class="rg-announcement-icon">
                                <i class="bi {{ $item->icon ?? 'bi-megaphone-fill' }}"></i>
                            </span>
                            <div class="rg-announcement-copy">
                                <div class="rg-announcement-title">
                                    {{ $item->title }}
                                    @if($item->badge)<span class="badge">{{ $item->badge }}</span>@endif
                                </div>
                                <div class="rg-announcement-meta">
                                    {{ optional($item->published_at)->format('M d, Y') ?? '—' }}
                                    @if($item->author) · by {{ $item->author->name }} @endif
                                </div>
                                <p class="rg-announcement-excerpt">{{ Str::limit($item->body, 180) }}</p>
                            </div>
                        </div>
                        <span class="rg-announcement-state {{ $item->is_published ? 'is-published' : 'is-hidden' }}">
                            <span aria-hidden="true"></span>{{ $item->is_published ? 'Published' : 'Hidden' }}
                        </span>
                    </div>
                    <div class="rg-announcement-actions">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-ann-edit="{{ json_encode($item->only(['id', 'title', 'body', 'badge', 'icon', 'is_published'])) }}"><i class="bi bi-pencil me-1"></i>Edit</button>
                        <form method="POST" action="{{ route('admin.announcements.toggle', $item) }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm {{ $item->is_published ? 'btn-outline-secondary' : 'btn-outline-success' }}">
                                <i class="bi {{ $item->is_published ? 'bi-eye-slash' : 'bi-eye' }}"></i> {{ $item->is_published ? 'Unpublish' : 'Publish' }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.announcements.destroy', $item) }}" class="d-inline" onsubmit="return confirm('Delete this announcement?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="Delete {{ $item->title }}"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="text-center text-muted py-4">
                    @if(\App\Models\Announcement::listFiltersActive(request(), true))
                        No announcements match these filters.
                    @else
                        No announcements yet. Create one to show it on the landing page.
                    @endif
                </div>
            @endforelse
        </div>
    </div>
    @if($announcements->hasPages())
    <div class="mt-3">
        {{ $announcements->links('pagination::bootstrap-5') }}
    </div>
    @endif

    {{-- Create / Edit Modal --}}
    <div class="modal fade" id="annModal" tabindex="-1" aria-labelledby="annModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
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
                            <input type="hidden" name="is_published" value="0">
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
            bootstrap.Modal.getOrCreateInstance(document.getElementById('annModal')).show();
        }
        document.addEventListener('click', function (event) {
            const button = event.target.closest('[data-ann-edit]');
            if (!button) return;
            openEditModal(JSON.parse(button.getAttribute('data-ann-edit')));
        });
    </script>
    @endpush
</x-app-layout>
