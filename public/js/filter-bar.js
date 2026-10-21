/**
 * Powers every <x-filters.toolbar> instance (resources/views/components/filters/toolbar.blade.php).
 *
 * Deliberately stays a progressive enhancement over a plain GET <form>:
 * the form still submits and paginates normally, while Alpine adds chips
 * and preserves unrelated query parameters on explicit submission.
 * If Alpine ever fails to load, the form still works as a normal GET form.
 */
document.addEventListener('alpine:init', () => {
    window.Alpine.data('raniagFilterBar', () => ({
        chips: [],

        init() {
            this.buildChips();
            this.$refs.form?.addEventListener('submit', () => this.preserveQueryParameters());
        },

        // Reads whatever named fields exist in the form right now — the
        // built-in search/date range plus anything a page passed into the
        // toolbar's slot — and turns each non-empty one into a chip. This
        // is what lets any page reuse the toolbar and get chips "for free"
        // without describing its own filters to this component.
        buildChips() {
            const form = this.$refs.form;
            if (!form) return;

            const chips = [];
            form.querySelectorAll('input[name], select[name]').forEach((field) => {
                if (!field.name || field.type === 'hidden' || field.disabled || field.hasAttribute('data-filter-ignore')) return;

                const value = field.value;
                const defaultValue = field.dataset.filterDefault ?? '';
                if (!value || value === defaultValue) return;

                let valueLabel = value;
                if (field.tagName === 'SELECT') {
                    const opt = field.options[field.selectedIndex];
                    valueLabel = opt ? opt.textContent.trim() : value;
                }

                const wrapLabel = field.closest('label')?.querySelector('span')
                    || field.closest('div')?.querySelector('label');
                const fieldLabel = wrapLabel
                    ? wrapLabel.textContent.trim()
                    : field.name.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());

                chips.push({ name: field.name, text: `${fieldLabel}: ${valueLabel}` });
            });

            this.chips = chips;
        },

        // Delegated at the form level so built-in and slot-provided fields
        // share the same staged chip updates without submitting the form.
        onFieldChange() {
            this.buildChips();
        },

        // Keep sort, tab, and other unrelated query state when applying a
        // filter, without overriding the current values in this form.
        preserveQueryParameters() {
            const form = this.$refs.form;
            if (!form) return;

            new URLSearchParams(window.location.search).forEach((value, name) => {
                if (name === 'page' || form.elements.namedItem(name)) return;

                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = value;
                form.append(input);
            });
        },

        removeChip(name) {
            const form = this.$refs.form;
            const field = form?.querySelector(`[name="${CSS.escape(name)}"]`);
            if (!field) return;
            field.value = field.dataset.filterDefault ?? '';
            this.buildChips();
        },
    }));
});

// Native <details> keeps filters usable without Alpine. These small
// enhancements make dismissal and state explicit for pointer and keyboard
// users while leaving the GET form behavior untouched.
(() => {
    const syncExpanded = (menu) => {
        const summary = menu?.querySelector(':scope > summary');
        if (summary) summary.setAttribute('aria-expanded', String(menu.open));
    };
    const getPopover = (menu) => menu?.querySelector(':scope > .rg-filter-popover, :scope > .rg-queue-filter-popover');
    const usesCompactViewport = () => window.matchMedia(
        '(max-width: 767.98px), (orientation: landscape) and (max-height: 600px)'
    ).matches;
    const positionMobilePopover = (menu) => {
        const popover = getPopover(menu);
        const summary = menu?.querySelector(':scope > summary');
        if (!popover || !summary || !menu.open) return;

        if (!usesCompactViewport()) {
            popover.style.removeProperty('position');
            popover.style.removeProperty('top');
            popover.style.removeProperty('left');
            popover.style.removeProperty('right');
            popover.style.removeProperty('width');
            popover.style.removeProperty('max-height');
            popover.style.removeProperty('z-index');
            return;
        }

        const viewportHeight = window.visualViewport?.height || window.innerHeight;
        const header = document.querySelector('.navbar-top');
        const headerBottom = header ? Math.max(0, header.getBoundingClientRect().bottom) : 0;
        const dock = document.querySelector('.mobile-dock');
        const dockTop = dock && getComputedStyle(dock).display !== 'none'
            ? dock.getBoundingClientRect().top
            : viewportHeight;
        const anchor = summary.getBoundingClientRect().bottom + 8;
        const top = Math.max(headerBottom + 8, Math.min(anchor, viewportHeight - 180));
        const maxHeight = Math.max(160, Math.min(dockTop, viewportHeight) - top - 12);

        Object.assign(popover.style, {
            position: 'fixed',
            top: `${top}px`,
            left: 'max(.7rem, env(safe-area-inset-left))',
            right: 'max(.7rem, env(safe-area-inset-right))',
            width: 'auto',
            maxHeight: `${maxHeight}px`,
            zIndex: '1060',
        });
    };

    document.querySelectorAll('.rg-filter-menu, .rg-queue-filter-menu').forEach(syncExpanded);
    document.addEventListener('toggle', (event) => {
        if (!event.target.matches?.('.rg-filter-menu, .rg-queue-filter-menu')) return;
        syncExpanded(event.target);
        if (event.target.open) {
            requestAnimationFrame(() => positionMobilePopover(event.target));
        } else {
            getPopover(event.target)?.removeAttribute('style');
        }
    }, true);
    window.addEventListener('resize', () => {
        document.querySelectorAll('.rg-filter-menu[open], .rg-queue-filter-menu[open]').forEach(positionMobilePopover);
    }, { passive: true });
    window.addEventListener('scroll', () => {
        document.querySelectorAll('.rg-filter-menu[open], .rg-queue-filter-menu[open]').forEach(positionMobilePopover);
    }, { passive: true });
    window.visualViewport?.addEventListener('resize', () => {
        document.querySelectorAll('.rg-filter-menu[open], .rg-queue-filter-menu[open]').forEach(positionMobilePopover);
    }, { passive: true });

    document.addEventListener('click', (event) => {
        document.querySelectorAll('.rg-filter-menu[open]').forEach((menu) => {
            if (!menu.contains(event.target)) {
                menu.open = false;
                syncExpanded(menu);
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        const menu = event.target.closest?.('.rg-filter-menu[open]')
            || document.querySelector('.rg-filter-menu[open]');
        if (!menu) return;
        event.preventDefault();
        menu.open = false;
        syncExpanded(menu);
        menu.querySelector(':scope > summary')?.focus();
    });
})();
