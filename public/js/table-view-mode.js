(() => {
    const roots = document.querySelectorAll('[data-view-mode-root]');

    roots.forEach((root) => {
        let table = root.querySelector('table.rg-data-table, table');
        const choices = root.querySelectorAll('[data-view-mode-choice]');
        if (!table || !choices.length) return;

        const storageKey = `raniag:view:${root.dataset.viewModeUser}:${root.dataset.viewModeRoot}`;
        const labelCells = () => {
            table = root.querySelector('table.rg-data-table, table');
            if (!table) return;
            const labels = Array.from(table.querySelectorAll('thead th')).map((heading) => heading.textContent.replace(/\s+/g, ' ').trim());
            table.querySelectorAll('tbody tr').forEach((row) => {
                Array.from(row.cells).forEach((cell, index) => {
                    if (cell.colSpan === 1 && labels[index]) cell.dataset.label = labels[index];
                });
            });
        };
        const setMode = (mode, persist = true) => {
            const selected = mode === 'cards' ? 'cards' : 'table';
            root.dataset.viewMode = selected;
            choices.forEach((button) => {
                const active = button.dataset.viewModeChoice === selected;
                button.setAttribute('aria-pressed', String(active));
                button.classList.toggle('btn-primary', active);
                button.classList.toggle('btn-outline-secondary', !active);
            });
            if (persist) {
                try {
                    window.localStorage.setItem(storageKey, selected);
                } catch {
                    // The current selection still works when storage is unavailable.
                }
            }
        };

        labelCells();
        let savedMode = 'table';
        try {
            savedMode = window.localStorage.getItem(storageKey) || 'table';
        } catch {
            savedMode = 'table';
        }
        setMode(savedMode, false);
        choices.forEach((button) => button.addEventListener('click', () => setMode(button.dataset.viewModeChoice)));

        new MutationObserver(labelCells).observe(root, { childList: true, subtree: true });
    });
})();
