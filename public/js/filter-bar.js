/**
 * Powers every <x-filters.toolbar> instance (resources/views/components/filters/toolbar.blade.php).
 *
 * Deliberately stays a progressive enhancement over a plain GET <form>:
 * the form still submits and paginates exactly like before (Laravel's
 * withQueryString() etc. keep working untouched), Alpine just adds
 * chips + auto-submit + debounce on top. If Alpine ever fails to load,
 * the form still works as a normal filter form with a "Filter" button.
 */
document.addEventListener('alpine:init', () => {
    window.Alpine.data('raniagFilterBar', () => ({
        chips: [],

        init() {
            this.buildChips();
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
                if (!field.name || field.type === 'hidden') return;

                const value = field.value;
                const defaultValue = field.dataset.filterDefault ?? '';
                if (!value || value === defaultValue) return;

                let valueLabel = value;
                if (field.tagName === 'SELECT') {
                    const opt = field.options[field.selectedIndex];
                    valueLabel = opt ? opt.textContent.trim() : value;
                }

                const wrapLabel = field.closest('div')?.querySelector('label');
                const fieldLabel = wrapLabel
                    ? wrapLabel.textContent.trim()
                    : field.name.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());

                chips.push({ name: field.name, text: `${fieldLabel}: ${valueLabel}` });
            });

            this.chips = chips;
        },

        // Delegated at the form level so it covers select/date fields the
        // toolbar itself renders AND any select/date a page adds via the
        // slot — no per-field wiring needed on the page's side.
        onFieldChange(event) {
            const t = event.target;
            if (t.tagName === 'SELECT' || t.type === 'date') {
                this.submitForm();
            }
        },

        // requestSubmit() (not submit()) so the existing global
        // data-loading-message overlay — wired in layouts/app.blade.php to
        // every form's real 'submit' event — still fires for these
        // programmatic auto-submits, same as a manual "Filter" click.
        submitForm() {
            const form = this.$refs.form;
            if (!form) return;
            if (form.requestSubmit) {
                form.requestSubmit();
            } else {
                form.submit();
            }
        },

        removeChip(name) {
            const form = this.$refs.form;
            const field = form?.querySelector(`[name="${CSS.escape(name)}"]`);
            if (!field) return;
            field.value = field.dataset.filterDefault ?? '';
            this.submitForm();
        },
    }));
});
