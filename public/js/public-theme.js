(function () {
    const storageKey = 'raniag-public-theme';
    const root = document.documentElement;

    function readTheme() {
        try {
            return localStorage.getItem(storageKey) === 'dark' ? 'dark' : 'light';
        } catch (error) {
            console.warn('RANIAG theme preference could not be read:', error);
            return 'light';
        }
    }

    function updateControls(theme) {
        document.querySelectorAll('[data-rg-theme-toggle]').forEach((button) => {
            const nextTheme = theme === 'dark' ? 'light' : 'dark';
            button.setAttribute('aria-pressed', String(theme === 'dark'));
            button.setAttribute('aria-label', `Switch to ${nextTheme} mode`);

            const icon = button.querySelector('.bi');
            const label = button.querySelector('[data-rg-theme-label]');
            if (icon) icon.className = `bi ${theme === 'dark' ? 'bi-moon-stars' : 'bi-sun'}`;
            if (label) label.textContent = `${theme === 'dark' ? 'Dark' : 'Light'} mode`;
        });
    }

    function applyTheme(theme) {
        root.dataset.publicTheme = theme;
        root.dataset.theme = theme;
        root.style.colorScheme = theme;
        updateControls(theme);
    }

    applyTheme(readTheme());
    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-rg-theme-toggle]');
        if (!button) return;

        const theme = root.dataset.publicTheme === 'dark' ? 'light' : 'dark';
        applyTheme(theme);
        try {
            localStorage.setItem(storageKey, theme);
        } catch (error) {
            console.warn('RANIAG theme preference could not be saved:', error);
        }
    });

    window.addEventListener('storage', (event) => {
        if (event.key === storageKey) applyTheme(event.newValue === 'dark' ? 'dark' : 'light');
    });

    window.addEventListener('pageshow', () => applyTheme(readTheme()));
})();
