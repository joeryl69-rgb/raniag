<div class="dropdown" id="notif-bell-wrapper">
    <button class="btn btn-light position-relative rounded-circle d-flex align-items-center justify-content-center notif-bell-btn"
            type="button" id="notifBellToggle" data-bs-toggle="dropdown" aria-expanded="false"
            data-bs-display="static" data-bs-offset="0,4"
            aria-label="Notifications">
        <i class="bi bi-bell-fill text-secondary"></i>
        <span id="notifBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none">
            0<span class="visually-hidden">unread notifications</span>
        </span>
    </button>

    <div class="dropdown-menu dropdown-menu-end shadow border-0 p-0 notif-dropdown" aria-labelledby="notifBellToggle">
        <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
            <span class="fw-bold small text-uppercase text-muted">Notifications</span>
            <div class="d-flex align-items-center gap-2">
                <button type="button" id="notifMarkAllBtn" class="btn btn-link btn-sm p-0 text-decoration-none">Mark all read</button>
                <button type="button" id="notifClearAllBtn" class="btn btn-link btn-sm p-0 text-decoration-none text-danger" title="Move all to bin">
                    <i class="bi bi-trash"></i>
                </button>
                <button type="button" class="btn-close d-lg-none" id="notifCloseBtn" aria-label="Close"></button>
            </div>
        </div>

        <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom notif-bulkbar d-none" id="notifBulkBar">
            <div class="form-check mb-0">
                <input type="checkbox" class="form-check-input" id="notifSelectAll">
                <label class="form-check-label small" for="notifSelectAll">Select all</label>
            </div>
            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-danger d-none" id="notifDeleteSelectedBtn" disabled>
                <i class="bi bi-trash me-1"></i>Move to bin <span id="notifSelectedCount"></span>
            </button>
        </div>

        <div id="notifList" class="notif-list">
            <div class="text-center text-muted small py-5" id="notifEmptyState">
                <i class="bi bi-bell-slash fs-1 d-block mb-2 opacity-50"></i>
                No notifications yet
            </div>
        </div>

        <a href="{{ route('notifications.index') }}" class="d-block text-center small py-2 border-top text-decoration-none">
            View all notifications
        </a>
        <form id="notifClearAllForm" method="POST" action="{{ route('notifications.destroy_all') }}" class="d-none">
            @csrf
            @method('DELETE')
        </form>
        <form id="notifBulkDeleteForm" method="POST" action="{{ route('notifications.destroy_selected') }}" class="d-none">
            @csrf
            @method('DELETE')
        </form>
    </div>
</div>

@once
{{-- Inline <style> on purpose, not @push('styles') — see mobile-dock.blade.php
     for why: pushing from a component printed after @stack('styles') in
     layouts/app.blade.php's <head> was silently discarded. --}}
<style>
    .notif-bell-btn {
        width: 2.75rem;
        height: 2.75rem;
        border: 2px solid rgba(255,255,255,0.9);
        box-shadow: 0 0.3rem 0.8rem rgba(15, 23, 42, 0.12);
    }

    [data-theme="dark"] .notif-bell-btn {
        border-color: rgba(255,255,255,0.12);
        background-color: #1c2b47 !important;
    }

    .notif-dropdown {
        width: 340px;
        max-width: min(92vw, 340px);
        z-index: 1055;
    }

    .notif-list {
        max-height: 360px;
        min-height: 160px;
        overflow-y: auto;
    }

    @media (max-width: 767.98px) {
        /* Full-screen sheet — portaled to <body> by JS so it escapes
           .navbar-top's sticky stacking context (z-index:1015 root)
           which otherwise traps children below the sidebar (z-index:1040). */
        .notif-dropdown {
            position: fixed !important;
            top: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            left: 0 !important;
            height: 100% !important;
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
            border-radius: 0 !important;
            transform: none !important;
            overflow-x: hidden;
            z-index: 2075;
        }

        .notif-dropdown.show {
            display: flex;
            flex-direction: column;
        }

        .notif-list {
            max-height: none;
            flex: 1 1 auto;
            overflow-x: hidden;
        }

        body.rg-notif-open {
            overflow: hidden;
        }
    }

    /* Desktop/tablet: pin the popover to the right edge of the bell no
       matter what Popper's auto-flip decides (the sidebar layout can make
       Popper think there's more room on the left, which used to make the
       panel jump to the left of the screen instead of staying under the
       bell). This keeps placement identical/predictable on every wide
       screen — always right-aligned, never left, and always directly
       under the bell since .navbar-top is now sticky (same on-screen
       position on every page). */
    @media (min-width: 768px) {
        #notifCloseBtn { display: none !important; }

        .notif-dropdown {
            left: auto !important;
            right: 0 !important;
            top: 100% !important;
            margin-top: 0.5rem !important;
        }
    }
</style>
@endonce

@once
@push('scripts')
<script>
(function () {
    const POLL_URL = @json(route('notifications.poll'));
    const MARK_ALL_URL = @json(route('notifications.mark_all_read'));
    const CLEAR_ALL_URL = @json(route('notifications.destroy_all'));
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;

    const badge = document.getElementById('notifBadge');
    const list = document.getElementById('notifList');
    const emptyState = document.getElementById('notifEmptyState');
    const markAllBtn = document.getElementById('notifMarkAllBtn');
    const clearAllBtn = document.getElementById('notifClearAllBtn');
    const closeBtn = document.getElementById('notifCloseBtn');
    const bellToggle = document.getElementById('notifBellToggle');
    const bulkBar = document.getElementById('notifBulkBar');
    const selectAllCb = document.getElementById('notifSelectAll');
    const deleteSelectedBtn = document.getElementById('notifDeleteSelectedBtn');
    const bulkDeleteForm = document.getElementById('notifBulkDeleteForm');

    function syncBellSelection() {
        const boxes = Array.from(list.querySelectorAll('.notif-bell-checkbox'));
        const checked = boxes.filter(function (b) { return b.checked; });
        deleteSelectedBtn.classList.toggle('d-none', checked.length === 0);
        deleteSelectedBtn.disabled = checked.length === 0;
        document.getElementById('notifSelectedCount').textContent = checked.length > 0 ? '(' + checked.length + ')' : '';
        selectAllCb.checked = boxes.length > 0 && checked.length === boxes.length;
        selectAllCb.indeterminate = checked.length > 0 && checked.length < boxes.length;
    }

    if (selectAllCb) {
        selectAllCb.addEventListener('change', function () {
            list.querySelectorAll('.notif-bell-checkbox').forEach(function (b) { b.checked = selectAllCb.checked; });
            syncBellSelection();
        });
    }

    if (deleteSelectedBtn) {
        deleteSelectedBtn.addEventListener('click', function () {
            const ids = Array.from(list.querySelectorAll('.notif-bell-checkbox:checked')).map(function (b) { return b.value; });
            if (!ids.length || !confirm('Move ' + ids.length + ' selected notification(s) to the bin?')) return;
            bulkDeleteForm.querySelectorAll('input[name="ids[]"]').forEach(function (el) { el.remove(); });
            ids.forEach(function (id) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = id;
                bulkDeleteForm.appendChild(input);
            });
            fetch(bulkDeleteForm.action, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'X-HTTP-Method-Override': 'DELETE' },
                body: new FormData(bulkDeleteForm),
            }).finally(function () {
                selectAllCb.checked = false;
                poll();
            });
        });
    }

    // --- Mobile portal (capture-phase approach) ---
    // Root cause: .navbar-top has position:sticky + z-index:1015 → creates
    // a stacking context. The sidebar (position:fixed, z-index:1040) lives
    // at ROOT level, so it paints ABOVE the entire .navbar-top layer —
    // the dropdown's own z-index is irrelevant; it's invisible.
    //
    // Previous fix used shown.bs.dropdown to portal, but Bootstrap's global
    // click-outside listener fires at the same time, sees the menu is now
    // outside the .dropdown wrapper, and immediately hides it (hence "not
    // appearing"). Fix: intercept in CAPTURE phase — before Bootstrap's
    // bubble handler — so Bootstrap never sees the mobile click at all.
    // Desktop falls through to Bootstrap normally.
    const notifMenu = document.querySelector('#notif-bell-wrapper .notif-dropdown');
    const notifSlot  = document.createComment('notif-menu-slot');
    let mobileOpen   = false;
    let notifPortaled = false;
    const isMob = function () { return window.matchMedia('(max-width: 767.98px)').matches; };

    // Inline styles with setProperty(..., 'important') always win over any
    // external stylesheet, regardless of load order, media-query matching,
    // or cache — removes all dependency on the CSS above actually landing.
    function forceSheetStyles(el) {
        const set = function (prop, val) { el.style.setProperty(prop, val, 'important'); };
        set('position', 'fixed');
        set('top', '0');
        set('right', '0');
        set('bottom', '0');
        set('left', '0');
        set('width', '100%');
        set('height', '100%');
        set('max-width', '100%');
        set('margin', '0');
        set('border-radius', '0');
        set('transform', 'none');
        set('z-index', '2147483647'); // max valid z-index — nothing can sit above it
        set('display', 'flex');
        set('flex-direction', 'column');
        set('overflow-x', 'hidden');
        set('overflow-y', 'auto');
    }

    function clearSheetStyles(el) {
        ['position', 'top', 'right', 'bottom', 'left', 'width', 'height', 'max-width',
         'margin', 'border-radius', 'transform', 'z-index', 'display', 'flex-direction',
         'overflow-x', 'overflow-y'].forEach(function (p) { el.style.removeProperty(p); });
    }

    function openMobileSheet() {
        if (!notifPortaled) {
            notifMenu.parentNode.insertBefore(notifSlot, notifMenu);
            document.body.appendChild(notifMenu);
            notifPortaled = true;
        }
        notifMenu.classList.add('show');
        forceSheetStyles(notifMenu);
        document.body.classList.add('rg-notif-open');
        if (bellToggle) bellToggle.setAttribute('aria-expanded', 'true');
        mobileOpen = true;
    }

    function closeMobileSheet() {
        notifMenu.classList.remove('show');
        clearSheetStyles(notifMenu);
        document.body.classList.remove('rg-notif-open');
        if (bellToggle) bellToggle.setAttribute('aria-expanded', 'false');
        mobileOpen = false;
        if (notifPortaled && notifSlot.parentNode) {
            notifSlot.parentNode.replaceChild(notifMenu, notifSlot);
            notifPortaled = false;
        }
    }

    if (bellToggle) {
        // Capture phase (3rd arg = true) → runs before Bootstrap's bubble handler.
        bellToggle.addEventListener('click', function (e) {
            if (!isMob()) return;           // desktop: Bootstrap handles it
            e.stopImmediatePropagation();   // prevent Bootstrap toggle
            e.preventDefault();
            mobileOpen ? closeMobileSheet() : openMobileSheet();
        }, true);

        // Desktop only: Bootstrap fires hidden.bs.dropdown → clean up scroll lock.
        bellToggle.addEventListener('hidden.bs.dropdown', function () {
            document.body.classList.remove('rg-notif-open');
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', function () {
            if (isMob()) {
                closeMobileSheet();
            } else if (window.bootstrap) {
                bootstrap.Dropdown.getOrCreateInstance(bellToggle).hide();
            }
        });
    }

    function timeIcon(icon) {
        return '<i class="bi ' + icon + '"></i>';
    }

    function render(data) {
        if (data.unread_count > 0) {
            badge.textContent = data.unread_count > 9 ? '9+' : data.unread_count;
            badge.classList.remove('d-none');
        } else {
            badge.classList.add('d-none');
        }

        if (!data.notifications.length) {
            list.innerHTML = '';
            list.appendChild(emptyState);
            bulkBar.classList.add('d-none');
            return;
        }

        bulkBar.classList.remove('d-none');

        list.innerHTML = data.notifications.map(function (n) {
            const unreadDot = n.is_read ? '' : '<span class="rounded-circle bg-primary d-inline-block flex-shrink-0" style="width:8px;height:8px;margin-top:6px;"></span>';
            return '<div class="notif-item-row d-flex align-items-stretch border-bottom' + (n.is_read ? '' : ' bg-light') + '">' +
                '<label class="d-flex align-items-center px-2 flex-shrink-0">' +
                    '<input type="checkbox" class="form-check-input notif-bell-checkbox m-0" value="' + n.id + '" data-delete-url="' + n.delete_url + '">' +
                '</label>' +
                '<a href="#" class="notif-item text-decoration-none d-flex gap-2 py-2 pe-2 text-dark flex-grow-1 min-w-0" ' +
                'data-read-url="' + n.read_url + '" data-target-url="' + (n.target_url || '') + '">' +
                '<div class="text-' + n.color + ' fs-5 flex-shrink-0">' + timeIcon(n.icon) + '</div>' +
                '<div class="flex-grow-1 min-w-0">' +
                    '<div class="fw-semibold small text-truncate">' + n.title + '</div>' +
                    '<div class="small text-muted text-truncate">' + n.message + '</div>' +
                    '<div class="small text-muted" style="font-size: 0.72rem;">' + n.created_at + '</div>' +
                '</div>' +
                unreadDot +
                '</a>' +
                '<button type="button" class="btn btn-sm btn-link text-danger notif-delete-btn px-2" data-delete-url="' + n.delete_url + '" title="Move to bin"><i class="bi bi-trash"></i></button>' +
            '</div>';
        }).join('');

        syncBellSelection();
    }

    function poll() {
        fetch(POLL_URL, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) { if (data) render(data); })
            .catch(function () { /* silent — notifications are non-critical, next poll retries */ });
    }

    list.addEventListener('change', function (e) {
        if (e.target.classList.contains('notif-bell-checkbox')) {
            syncBellSelection();
        }
    });

    list.addEventListener('click', function (e) {
        const item = e.target.closest('.notif-item');
        if (!item) return;
        e.preventDefault();

        fetch(item.dataset.readUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        }).finally(function () {
            if (item.dataset.targetUrl) {
                window.location.href = item.dataset.targetUrl;
            } else {
                poll();
            }
        });
    });

    markAllBtn.addEventListener('click', function () {
        fetch(MARK_ALL_URL, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        }).finally(poll);
    });

    if (clearAllBtn) {
        clearAllBtn.addEventListener('click', function () {
            if (!confirm('Move all notifications to the bin? This cannot be undone.')) return;
            fetch(CLEAR_ALL_URL, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json',
                    'X-HTTP-Method-Override': 'DELETE',
                },
            }).finally(poll);
        });
    }

    list.addEventListener('click', function (e) {
        const delBtn = e.target.closest('.notif-delete-btn');
        if (!delBtn) return;
        e.preventDefault();
        e.stopPropagation();

        fetch(delBtn.dataset.deleteUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json',
                'X-HTTP-Method-Override': 'DELETE',
            },
        }).finally(poll);
    });

    poll();
    setInterval(poll, 20000);
})();
</script>
@endpush
@endonce
