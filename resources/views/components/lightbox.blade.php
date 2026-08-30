{{-- Global image lightbox. Any <a> or <img> with class="js-lightbox" and a
     matching data-group opens here instead of a new browser tab. When two or
     more images share the same data-group, prev/next arrows appear — the
     same pattern used by the GPS-camera photo popup in the public report
     form, now shared everywhere (incident evidence, case documents, etc). --}}
<div class="modal fade" id="raniagLightbox" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content bg-dark border-0">
            <div class="modal-header border-0 py-2">
                <span class="text-white-50 small" id="lightboxCaption"></span>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body d-flex align-items-center justify-content-center position-relative p-0" style="min-height:60vh;">
                <button type="button" id="lightboxPrev" class="btn btn-dark position-absolute start-0 top-50 translate-middle-y ms-2 rounded-circle d-none" style="width:44px;height:44px;z-index:5;">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <img id="lightboxImage" src="" alt="Preview" class="img-fluid" style="max-height:80vh;object-fit:contain;">
                <button type="button" id="lightboxNext" class="btn btn-dark position-absolute end-0 top-50 translate-middle-y me-2 rounded-circle d-none" style="width:44px;height:44px;z-index:5;">
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>
            <div class="modal-footer border-0 py-2 justify-content-center">
                <a id="lightboxDownload" href="" target="_blank" class="btn btn-sm btn-outline-light">
                    <i class="bi bi-box-arrow-up-right me-1"></i>Open Original
                </a>
            </div>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function () {
    let groupItems = [];
    let currentIndex = 0;
    let modalInstance = null;

    function collectGroup(group) {
        return Array.from(document.querySelectorAll(`.js-lightbox[data-group="${CSS.escape(group)}"]`))
            .map(el => ({ src: el.dataset.src || el.getAttribute('href') || el.src, caption: el.dataset.caption || '' }));
    }

    function show(index) {
        currentIndex = index;
        const item = groupItems[currentIndex];
        if (!item) return;
        document.getElementById('lightboxImage').src = item.src;
        document.getElementById('lightboxDownload').href = item.src;
        document.getElementById('lightboxCaption').textContent = item.caption || (groupItems.length > 1 ? `Image ${currentIndex + 1} of ${groupItems.length}` : '');
        const multi = groupItems.length > 1;
        document.getElementById('lightboxPrev').classList.toggle('d-none', !multi);
        document.getElementById('lightboxNext').classList.toggle('d-none', !multi);
    }

    function openLightbox(el) {
        const group = el.dataset.group || ('single-' + Math.random());
        groupItems = collectGroup(group);
        const src = el.dataset.src || el.getAttribute('href') || el.src;
        const startIndex = Math.max(0, groupItems.findIndex(i => i.src === src));
        modalInstance = modalInstance || new bootstrap.Modal(document.getElementById('raniagLightbox'));
        show(startIndex);
        modalInstance.show();
    }

    document.addEventListener('click', function (e) {
        const trigger = e.target.closest('.js-lightbox');
        if (!trigger) return;
        e.preventDefault();
        openLightbox(trigger);
    });

    document.getElementById('lightboxPrev').addEventListener('click', () => show((currentIndex - 1 + groupItems.length) % groupItems.length));
    document.getElementById('lightboxNext').addEventListener('click', () => show((currentIndex + 1) % groupItems.length));

    document.addEventListener('keydown', function (e) {
        const el = document.getElementById('raniagLightbox');
        if (!el.classList.contains('show')) return;
        if (e.key === 'ArrowLeft') document.getElementById('lightboxPrev').click();
        if (e.key === 'ArrowRight') document.getElementById('lightboxNext').click();
    });
})();
</script>
@endpush
@endonce
