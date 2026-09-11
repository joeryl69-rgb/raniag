<x-app-layout>
    <x-slot name="header">
        {{ __('System Settings') }}
    </x-slot>

    <p class="small text-muted mb-3">Control the system appearance and browser notifications from a single, consistent settings area.</p>

    <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf
        @method('PUT')
        <input type="hidden" name="theme_key" id="selectedThemeKey" value="{{ $setting->theme_key }}">

        <div class="card border-0 shadow-sm mb-4 settings-card">
            <div class="card-header py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-display me-2 text-primary"></i>Appearance</h6>
            </div>
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
                    <div>
                        <div class="fw-semibold">Follow system appearance</div>
                        <div class="small text-muted">Use the same light or dark theme as your device.</div>
                    </div>
                    <div class="form-check form-switch ms-md-auto mb-0">
                        <input class="form-check-input settings-toggle" type="checkbox" role="switch" id="systemThemeSwitch">
                    </div>
                </div>

                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div>
                        <div class="fw-semibold">Dark mode</div>
                        <div class="small text-muted">Apply a dark interface layer over the current theme.</div>
                    </div>
                    <div class="form-check form-switch ms-md-auto mb-0">
                        <input class="form-check-input settings-toggle" type="checkbox" role="switch" id="darkModeSwitch" name="dark_mode" value="1" {{ $setting->dark_mode ? 'checked' : '' }}>
                    </div>
                </div>
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
                    <div class="form-check form-switch ms-md-auto mb-0">
                        <input class="form-check-input settings-toggle" id="pushPermissionSwitch" type="checkbox" role="switch" aria-label="Toggle push notifications">
                    </div>
                </div>
                <div id="pushNotifStatus" class="small mt-3 mb-0 text-muted">Notifications are currently off.</div>
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

    .settings-toggle {
        width: 3.2rem;
        height: 1.8rem;
        border-radius: 999px;
        cursor: pointer;
        background-color: rgba(148, 163, 184, 0.5);
        border: 1px solid rgba(148, 163, 184, 0.7);
        box-shadow: inset 0 0 0 1px rgba(255,255,255,0.08);
    }

    .settings-toggle:checked {
        background-color: var(--raniag-primary);
        border-color: var(--raniag-primary);
    }

    .settings-toggle:focus {
        box-shadow: 0 0 0 0.2rem var(--raniag-primary-light);
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
