<x-app-layout>
    <x-slot name="header">
        {{ __('System Settings') }}
    </x-slot>

    <p class="small text-muted mb-3">Set the whole system to your preferred look, sync it with your device appearance, and control notifications from one place.</p>

    <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf
        @method('PUT')
        <input type="hidden" name="theme_key" id="selectedThemeKey" value="{{ $setting->theme_key }}">

        <div class="card border-0 shadow-sm mb-4 settings-card">
            <div class="card-header py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-display me-2 text-primary"></i>Appearance</h6>
            </div>
            <div class="card-body">
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" role="switch" id="systemThemeSwitch">
                    <label class="form-check-label" for="systemThemeSwitch">Follow system appearance</label>
                </div>

                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="darkModeSwitch" name="dark_mode" value="1" {{ $setting->dark_mode ? 'checked' : '' }}>
                    <label class="form-check-label" for="darkModeSwitch">Dark mode override</label>
                </div>
                <div class="form-text mt-2">When the system setting is active, the app follows your device theme automatically. Dark mode overrides the selected palette while the theme swatches stay locked to avoid poor contrast combinations.</div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4 settings-card">
            <div class="card-header py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-palette-fill me-2 text-primary"></i>Color Theme</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach ($presets as $key => $preset)
                        <div class="col-6 col-md-4 col-lg-2">
                            <button type="button"
                                    class="theme-swatch-btn w-100 border rounded-3 p-3 text-center {{ $setting->theme_key === $key ? 'selected' : '' }}"
                                    data-theme-key="{{ $key }}"
                                    onclick="selectTheme('{{ $key }}')">
                                <span class="d-inline-block rounded-circle mb-2" style="width:2.25rem;height:2.25rem;background-color:{{ $preset['swatch'] }};"></span>
                                <div class="small fw-semibold">{{ $preset['label'] }}</div>
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4 settings-card">
            <div class="card-header py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-bell-fill me-2 text-primary"></i>Notifications</h6>
            </div>
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div>
                        <div class="fw-semibold">Push notifications</div>
                        <div class="small text-muted">Allow the app to send browser notifications even when the tab is closed.</div>
                    </div>
                    <button type="button" class="btn btn-primary px-3" id="pushNotifToggleBtn">
                        <span id="pushNotifToggleLabel">Enable Push Notifications</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save Settings</button>
            <button type="submit" form="resetThemeForm" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset to Default</button>
        </div>
    </form>

    <form method="POST" action="{{ route('admin.settings.reset') }}" id="resetThemeForm" class="d-none">
        @csrf
    </form>

@push('styles')
<style>
    .settings-card {
        background-color: var(--raniag-surface);
        border: 1px solid var(--raniag-border) !important;
    }

    .settings-card .card-header {
        background-color: transparent;
        border-bottom: 1px solid var(--raniag-border);
    }

    .theme-swatch-btn {
        background-color: rgba(255,255,255,0.7);
        border-color: var(--raniag-border);
        cursor: pointer;
        transition: box-shadow 0.15s ease, border-color 0.15s ease, opacity 0.15s ease, transform 0.15s ease;
    }

    .theme-swatch-btn:hover:not(:disabled) {
        transform: translateY(-1px);
        border-color: var(--raniag-primary) !important;
        box-shadow: 0 0 0 0.2rem var(--raniag-primary-light);
    }

    .theme-swatch-btn.selected {
        border-color: var(--raniag-primary) !important;
        box-shadow: 0 0 0 0.2rem var(--raniag-primary-light);
    }

    .theme-swatch-btn:disabled {
        opacity: 0.45;
        cursor: not-allowed;
    }
</style>
@endpush

@push('scripts')
<script>
    const THEME_VARS = @json(collect($presets)->map(fn ($p) => $p['vars']));
    const themeButtons = [...document.querySelectorAll('.theme-swatch-btn')];
    const darkModeSwitch = document.getElementById('darkModeSwitch');
    const systemThemeSwitch = document.getElementById('systemThemeSwitch');

    function applyPreview(themeKey, darkMode) {
        const vars = THEME_VARS[themeKey];
        if (!vars) return;
        const root = document.documentElement;
        Object.entries(vars).forEach(([name, value]) => root.style.setProperty(name, value));

        if (darkMode) {
            root.style.setProperty('--raniag-surface', '#0f172a');
            root.style.setProperty('--raniag-border', '#253449');
            root.style.setProperty('--raniag-sidebar', '#0f172a');
            root.style.setProperty('--raniag-sidebar-active', '#1e293b');
        }
    }

    function syncThemeAvailability() {
        const followSystem = systemThemeSwitch.checked;
        const forceDark = darkModeSwitch.checked;
        const shouldDisable = followSystem || forceDark;

        themeButtons.forEach((btn) => {
            btn.disabled = shouldDisable;
            btn.setAttribute('aria-disabled', String(shouldDisable));
            btn.title = followSystem ? 'Theme is synced to system appearance.' : forceDark ? 'Theme selection is disabled while dark mode is active.' : 'Choose a theme';
        });

        if (followSystem) {
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            applyPreview(document.getElementById('selectedThemeKey').value, prefersDark);
        } else {
            applyPreview(document.getElementById('selectedThemeKey').value, forceDark);
        }
    }

    function selectTheme(key) {
        if (systemThemeSwitch.checked || darkModeSwitch.checked) return;
        document.getElementById('selectedThemeKey').value = key;
        themeButtons.forEach((btn) => {
            btn.classList.toggle('selected', btn.dataset.themeKey === key);
        });
        applyPreview(key, false);
    }

    darkModeSwitch.addEventListener('change', function () {
        syncThemeAvailability();
    });

    systemThemeSwitch.addEventListener('change', function () {
        syncThemeAvailability();

        if (this.checked) {
            darkModeSwitch.checked = window.matchMedia('(prefers-color-scheme: dark)').matches;
        }
    });

    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function () {
        if (systemThemeSwitch.checked) {
            syncThemeAvailability();
        }
    });

    themeButtons.forEach((btn) => {
        btn.classList.toggle('selected', btn.dataset.themeKey === '{{ $setting->theme_key }}');
    });

    syncThemeAvailability();
</script>
@endpush
</x-app-layout>
