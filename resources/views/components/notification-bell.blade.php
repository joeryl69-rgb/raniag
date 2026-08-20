<div class="dropdown" id="notif-bell-wrapper">
    <button class="btn btn-light position-relative rounded-circle d-flex align-items-center justify-content-center"
            style="width: 2.5rem; height: 2.5rem;"
            type="button" id="notifBellToggle" data-bs-toggle="dropdown" aria-expanded="false"
            aria-label="Notifications">
        <i class="bi bi-bell-fill text-secondary"></i>
        <span id="notifBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none" style="font-size: 0.65rem;">
            0<span class="visually-hidden">unread notifications</span>
        </span>
    </button>

    <div class="dropdown-menu dropdown-menu-end shadow border-0 p-0" style="width: 340px; max-width: 90vw;" aria-labelledby="notifBellToggle">
        <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
            <span class="fw-bold small text-uppercase text-muted">Notifications</span>
            <button type="button" id="notifMarkAllBtn" class="btn btn-link btn-sm p-0 text-decoration-none">Mark all read</button>
        </div>

        <div id="notifList" style="max-height: 360px; overflow-y: auto;">
            <div class="text-center text-muted small py-4" id="notifEmptyState">
                <i class="bi bi-bell-slash fs-4 d-block mb-1"></i>
                No notifications yet
            </div>
        </div>

        <a href="{{ route('notifications.index') }}" class="d-block text-center small py-2 border-top text-decoration-none">
            View all notifications
        </a>
    </div>
</div>

@once
@push('scripts')
<script>
(function () {
    const POLL_URL = @json(route('notifications.poll'));
    const MARK_ALL_URL = @json(route('notifications.mark_all_read'));
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;

    const badge = document.getElementById('notifBadge');
    const list = document.getElementById('notifList');
    const emptyState = document.getElementById('notifEmptyState');
    const markAllBtn = document.getElementById('notifMarkAllBtn');

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
            return;
        }

        list.innerHTML = data.notifications.map(function (n) {
            const unreadDot = n.is_read ? '' : '<span class="rounded-circle bg-primary d-inline-block flex-shrink-0" style="width:8px;height:8px;margin-top:6px;"></span>';
            return '<a href="#" class="notif-item text-decoration-none d-flex gap-2 px-3 py-2 border-bottom text-dark' + (n.is_read ? '' : ' bg-light') + '" ' +
                'data-read-url="' + n.read_url + '" data-target-url="' + (n.target_url || '') + '">' +
                '<div class="text-' + n.color + ' fs-5 flex-shrink-0">' + timeIcon(n.icon) + '</div>' +
                '<div class="flex-grow-1 min-w-0">' +
                    '<div class="fw-semibold small text-truncate">' + n.title + '</div>' +
                    '<div class="small text-muted text-truncate">' + n.message + '</div>' +
                    '<div class="small text-muted" style="font-size: 0.72rem;">' + n.created_at + '</div>' +
                '</div>' +
                unreadDot +
            '</a>';
        }).join('');
    }

    function poll() {
        fetch(POLL_URL, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) { if (data) render(data); })
            .catch(function () { /* silent — notifications are non-critical, next poll retries */ });
    }

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

    poll();
    setInterval(poll, 20000);
})();
</script>
@endpush
@endonce
