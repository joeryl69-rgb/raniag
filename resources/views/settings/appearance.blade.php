<x-app-layout>
    <x-slot name="header">
        {{ __('Appearance') }}
    </x-slot>

    <p class="small text-muted mb-3">These settings apply only to your own account — they won't change what anyone else sees.</p>

    <form method="POST" action="{{ route('settings.appearance.update') }}">
        @csrf
        @method('PUT')
        <input type="hidden" name="theme_key" id="selectedThemeKey" value="{{ $setting->theme_key }}">
        <input type="hidden" name="font_key" id="selectedFontKey" value="{{ $setting->font_key }}">
        <input type="hidden" name="font_size" id="selectedFontSize" value="{{ $setting->font_size }}">

        <div class="card border-0 shadow-sm mb-4 settings-card">
            <div class="card-header py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-display me-2 text-primary"></i>Appearance</h6>
            </div>
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3 pb-3 border-bottom">
                    <div>
                        <div class="fw-semibold">Follow system appearance</div>
                        <div class="small text-muted">Your device switches between light and dark automatically, based on your OS setting.</div>
                    </div>
                    <div class="ms-md-auto flex-shrink-0">
                        <input class="settings-toggle" type="checkbox" role="switch" id="systemThemeSwitch"
                               name="follow_system" value="1" {{ $setting->follow_system ? 'checked' : '' }}
                               {{ $setting->dark_mode ? 'disabled' : '' }}
                               aria-label="Toggle follow system appearance">
                    </div>
                </div>

                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3 pb-3 border-bottom">
                    <div>
                        <div class="fw-semibold">Dark mode</div>
                        <div class="small text-muted">Apply a dark interface layer over your current theme.</div>
                    </div>
                    <div class="ms-md-auto flex-shrink-0">
                        <input class="settings-toggle" type="checkbox" role="switch" id="darkModeSwitch"
                               name="dark_mode" value="1" {{ $setting->dark_mode ? 'checked' : '' }}
                               {{ $setting->follow_system ? 'disabled' : '' }}
                               aria-label="Toggle dark mode">
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="small fw-semibold text-muted d-block mb-2">Font</label>
                        <div class="row g-2">
                            @foreach ($fonts as $key => $font)
                                <div class="col-6 col-lg-4">
                                    <button type="button"
                                            class="font-swatch-btn w-100 border rounded-3 p-2 text-center {{ $setting->font_key === $key ? 'selected' : '' }}"
                                            data-font-key="{{ $key }}"
                                            style="font-family: {{ $font['stack'] }};"
                                            onclick="selectFont('{{ $key }}')">
                                        <div class="font-swatch-preview">{{ $font['preview'] }}</div>
                                        <div class="small">{{ $font['label'] }}</div>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="small fw-semibold text-muted d-block mb-2">
                            Text size — <span id="fontSizeCurrentLabel">{{ $fontSizes[$setting->font_size]['label'] ?? 'Default' }}</span>
                        </label>
                        @php
                            $fontSizeKeys = array_keys($fontSizes);
                            $fontSizeIndex = array_search($setting->font_size, $fontSizeKeys);
                            $fontSizeIndex = $fontSizeIndex === false ? 1 : $fontSizeIndex;
                        @endphp
                        <input type="range" class="form-range font-size-slider" id="fontSizeSlider"
                               min="0" max="{{ count($fontSizeKeys) - 1 }}" step="1" value="{{ $fontSizeIndex }}">
                        <div class="d-flex justify-content-between font-size-ticks">
                            @foreach ($fontSizes as $key => $size)
                                <span class="{{ $setting->font_size === $key ? 'active' : '' }}" data-font-size-key="{{ $key }}">{{ $size['label'] }}</span>
                            @endforeach
                        </div>
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
                                    class="theme-swatch-btn w-100 border rounded-3 p-3 text-center position-relative {{ $setting->theme_key === $key ? 'selected' : '' }}"
                                    data-theme-key="{{ $key }}"
                                    onclick="selectTheme('{{ $key }}')">
                                <i class="bi bi-check-circle-fill theme-swatch-check position-absolute top-0 end-0 m-2 text-primary"></i>
                                <span class="d-inline-block rounded-circle mb-2" style="width:2.25rem;height:2.25rem;background-color:{{ $preset['swatch'] }};"></span>
                                <div class="small fw-semibold">{{ $preset['label'] }}</div>
                            </button>
                        </div>
                    @endforeach
                </div>
                <p class="small text-muted mb-0 mt-2" id="themeDisabledNote" style="{{ ($setting->dark_mode || $setting->follow_system) ? '' : 'display:none;' }}">
                    <i class="bi bi-info-circle me-1"></i>Color theme is disabled while dark mode or "Follow system appearance" is on — dark mode uses its own palette.
                </p>
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
                    <div class="ms-md-auto flex-shrink-0">
                        <input class="settings-toggle" id="pushPermissionSwitch" type="checkbox" role="switch" aria-label="Toggle push notifications">
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

    <form method="POST" action="{{ route('settings.appearance.reset') }}" id="resetThemeForm" class="d-none">
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

    .theme-swatch-check {
        display: none;
        font-size: 1rem;
    }

    .theme-swatch-btn.selected .theme-swatch-check {
        display: block;
    }

    /* Font-size slider with tick labels underneath, replacing the old
       button group per the "horizontal bar with indicators" request. */
    .font-size-slider {
        accent-color: var(--raniag-primary);
    }

    .font-size-ticks {
        margin-top: 0.25rem;
    }

    .font-size-ticks span {
        font-size: 0.72rem;
        color: var(--bs-secondary-color, #6c757d);
        font-weight: 500;
    }

    .font-size-ticks span.active {
        color: var(--raniag-primary);
        font-weight: 700;
    }

    [data-theme="dark"] .font-size-ticks span {
        color: #8fa3b8;
    }

    [data-theme="dark"] .font-size-ticks span.active {
        color: var(--raniag-accent);
    }
</style>
@endpush

@push('scripts')
<script>
    const THEME_VARS = @json(collect($presets)->map(fn ($p) => $p['vars']));
    const FONT_SIZE_KEYS = @json($fontSizeKeys);
    const FONT_SIZE_LABELS = @json(collect($fontSizes)->map(fn ($s) => $s['label']));
    const FONT_SIZE_ROOT_PX = @json(collect($fontSizes)->map(fn ($s) => $s['root_px']));

    const themeButtons = [...document.querySelectorAll('.theme-swatch-btn')];
    const fontButtons = [...document.querySelectorAll('.font-swatch-btn')];
    const darkModeSwitch = document.getElementById('darkModeSwitch');
    const systemThemeSwitch = document.getElementById('systemThemeSwitch');
    const themeDisabledNote = document.getElementById('themeDisabledNote');
    const fontSizeSlider = document.getElementById('fontSizeSlider');
    const fontSizeCurrentLabel = document.getElementById('fontSizeCurrentLabel');
    const fontSizeTicks = [...document.querySelectorAll('.font-size-ticks span')];

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
            root.setAttribute('data-theme', 'dark');
        } else {
            root.setAttribute('data-theme', 'light');
        }
    }

    // Real disabling (not just auto-unchecking) — dark mode and "follow
    // system" are mutually exclusive, and color theme is meaningless while
    // either forces dark mode's own palette.
    function syncToggleAvailability() {
        const followSystem = systemThemeSwitch.checked;
        const forceDark = darkModeSwitch.checked;

        darkModeSwitch.disabled = followSystem;
        systemThemeSwitch.disabled = false;

        const themeDisabled = followSystem || forceDark;
        themeButtons.forEach((btn) => {
            btn.disabled = themeDisabled;
            btn.setAttribute('aria-disabled', String(themeDisabled));
        });
        themeDisabledNote.style.display = themeDisabled ? '' : 'none';

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
        themeButtons.forEach((btn) => btn.classList.toggle('selected', btn.dataset.themeKey === key));
        applyPreview(key, false);
    }

    function selectFont(key) {
        document.getElementById('selectedFontKey').value = key;
        fontButtons.forEach((btn) => btn.classList.toggle('selected', btn.dataset.fontKey === key));
        const swatch = document.querySelector('[data-font-key="' + key + '"]');
        if (swatch) {
            document.documentElement.style.setProperty('--raniag-font-family', getComputedStyle(swatch).fontFamily);
        }
    }

    function applyFontSizeByIndex(index) {
        const key = FONT_SIZE_KEYS[index];
        if (!key) return;
        document.getElementById('selectedFontSize').value = key;
        fontSizeCurrentLabel.textContent = FONT_SIZE_LABELS[key] || key;
        fontSizeTicks.forEach((tick) => tick.classList.toggle('active', tick.dataset.fontSizeKey === key));
        document.documentElement.style.fontSize = FONT_SIZE_ROOT_PX[key] || '16px';
    }

    fontSizeSlider.addEventListener('input', function () {
        applyFontSizeByIndex(parseInt(this.value, 10));
    });

    darkModeSwitch.addEventListener('change', function () {
        syncToggleAvailability();
    });

    systemThemeSwitch.addEventListener('change', function () {
        if (this.checked) {
            darkModeSwitch.checked = window.matchMedia('(prefers-color-scheme: dark)').matches;
        }
        syncToggleAvailability();
    });

    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function () {
        if (systemThemeSwitch.checked) {
            syncToggleAvailability();
        }
    });

    themeButtons.forEach((btn) => {
        btn.classList.toggle('selected', btn.dataset.themeKey === '{{ $setting->theme_key }}');
    });

    syncToggleAvailability();
</script>
@endpush
</x-app-layout>
