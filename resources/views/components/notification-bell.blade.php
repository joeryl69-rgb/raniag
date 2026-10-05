<div class="dropdown" id="notif-bell-wrapper">
    <button class="btn btn-light position-relative rounded-circle d-flex align-items-center justify-content-center notif-bell-btn"
            type="button" id="notifBellToggle" aria-expanded="false"
            aria-haspopup="true" aria-controls="notifPanel"
            aria-label="Notifications">
        <i class="bi bi-bell-fill text-secondary"></i>
        <span id="notifBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none">
            0<span class="visually-hidden">unread notifications</span>
        </span>
    </button>

    <div class="dropdown-menu shadow border-0 p-0 notif-dropdown" id="notifPanel" aria-labelledby="notifBellToggle">
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
        box-sizing: border-box;
        width: min(360px, calc(100vw - 16px));
        max-width: calc(100vw - 16px);
        max-height: min(420px, 70vh);
        z-index: 2075;
        overflow: hidden;
        border-radius: 14px;
    }

    .notif-dropdown.show {
        position: fixed !important;
        right: 8px !important;
        left: auto !important;
        transform: none !important;
        display: flex;
        flex-direction: column;
    }

    .notif-list {
        max-height: 280px;
        overflow-y: auto;
        overflow-x: hidden;
    }

    .sidebar-notif-count,
    .dock-notif-count {
        min-width: 1.15rem;
        height: 1.15rem;
        padding: 0 .35rem;
        border-radius: 999px;
        background: #dc3545;
        color: #fff;
        font-size: .68rem;
        font-weight: 800;
        line-height: 1.15rem;
        text-align: center;
    }

    .sidebar-notif-count { margin-left: auto; }
    .dock-bell { position: relative; display: inline-flex; }
    .dock-notif-count {
        position: absolute;
        top: -6px;
        right: -10px;
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

    // The panel is portaled to <body> so the sticky navbar cannot clip it,
    // and it stays a card under the bell instead of covering the page.
    const notifMenu = document.querySelector('#notif-bell-wrapper .notif-dropdown');
    const notifSlot = document.createComment('notif-menu-slot');
    let panelOpen = false;
    let notifPortaled = false;

    function placePanel() {
        if (!bellToggle || !notifMenu) return;
        const view = window.visualViewport;
        const viewWidth = view ? view.width : window.innerWidth;
        const viewLeft = view ? view.offsetLeft : 0;
        const margin = 8;
        const width = Math.min(360, Math.max(200, viewWidth - margin * 2));
        const rect = bellToggle.getBoundingClientRect();
        let left = rect.right - width;
        const minLeft = viewLeft + margin;
        const maxLeft = viewLeft + viewWidth - width - margin;
        left = Math.min(Math.max(minLeft, left), maxLeft);
        const top = Math.min(rect.bottom + 8, (view ? view.height : window.innerHeight) - 140);
        const maxHeight = Math.max(180, Math.min(420, (view ? view.height : window.innerHeight) - top - margin));
        const set = function (prop, val) { notifMenu.style.setProperty(prop, val, 'important'); };
        set('position', 'fixed');
        set('top', top + 'px');
        set('left', left + 'px');
        set('right', 'auto');
        set('bottom', 'auto');
        set('width', width + 'px');
        set('max-width', (viewWidth - margin * 2) + 'px');
        set('height', 'auto');
        set('max-height', maxHeight + 'px');
        set('margin', '0');
        set('transform', 'none');
        set('z-index', '2075');
        set('display', 'flex');
        set('flex-direction', 'column');
        set('overflow', 'hidden');
        set('box-sizing', 'border-box');
        set('border-radius', '14px');
    }

    function openPanel() {
        if (!notifMenu) return;
        if (!notifPortaled) {
            notifMenu.parentNode.insertBefore(notifSlot, notifMenu);
            document.body.appendChild(notifMenu);
            notifPortaled = true;
        }
        notifMenu.classList.add('show');
        document.getElementById('layer-panel')?.classList.remove('show');
        placePanel();
        if (bellToggle) bellToggle.setAttribute('aria-expanded', 'true');
        panelOpen = true;
    }

    function closePanel() {
        if (!notifMenu) return;
        notifMenu.classList.remove('show');
        ['position', 'top', 'left', 'right', 'bottom', 'width', 'height', 'max-height',
         'margin', 'transform', 'z-index', 'display', 'flex-direction', 'overflow', 'border-radius']
            .forEach(function (prop) { notifMenu.style.removeProperty(prop); });
        if (bellToggle) bellToggle.setAttribute('aria-expanded', 'false');
        panelOpen = false;
        if (notifPortaled && notifSlot.parentNode) {
            notifSlot.parentNode.replaceChild(notifMenu, notifSlot);
            notifPortaled = false;
        }
    }

    if (bellToggle) {
        bellToggle.addEventListener('click', function (e) {
            e.stopImmediatePropagation();
            e.preventDefault();
            panelOpen ? closePanel() : openPanel();
        }, true);
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', closePanel);
    }

    document.addEventListener('click', function (e) {
        if (!panelOpen) return;
        if (e.target.closest('#notif-bell-wrapper') || e.target.closest('.notif-dropdown')) return;
        closePanel();
    });

    window.addEventListener('resize', function () {
        if (panelOpen) placePanel();
    });

    function timeIcon(icon) {
        return '<i class="bi ' + icon + '"></i>';
    }

    function paintCount(el, count) {
        if (!el) return;
        if (count > 0) {
            el.textContent = count > 9 ? '9+' : String(count);
            el.classList.remove('d-none');
        } else {
            el.classList.add('d-none');
        }
    }

    function render(data) {
        const unread = data.unread_count || 0;
        paintCount(badge, unread);
        paintCount(document.getElementById('sidebarNotifBadge'), unread);
        paintCount(document.getElementById('dockNotifBadge'), unread);

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
                    '<div class="fw-semibold small">' + n.title + '</div>' +
                    '<div class="small text-muted">' + n.message + '</div>' +
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

    document.addEventListener('rg:poll-notifications', poll);
    poll();
    setInterval(poll, 5000);
})();
</script>
@endpush
@endonce
