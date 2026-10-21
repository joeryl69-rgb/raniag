<x-app-layout>
    <x-slot name="header">
        {{ __('System Settings') }}
    </x-slot>

    <main class="system-settings-page">
        <header class="settings-hero">
            <div>
                <span class="settings-eyebrow"><i class="bi bi-sliders2 me-2"></i>PERSONAL PREFERENCES</span>
                <h1>Make RANIAG<br><em>work your way.</em></h1>
                <p>Set your display, colors, and notification preferences. These choices apply only to your account.</p>
            </div>
            <div class="settings-hero-meta">
                <span class="settings-date"><i class="bi bi-calendar3"></i>{{ now()->format('l, M d, Y') }}</span>
                <span class="settings-private"><i class="bi bi-shield-check"></i>Private to your account</span>
            </div>
            <span class="settings-orbit settings-orbit-one" aria-hidden="true"></span>
            <span class="settings-orbit settings-orbit-two" aria-hidden="true"></span>
        </header>

    <form method="POST" action="{{ route('settings.appearance.update') }}" class="system-settings-form">
        @csrf
        @method('PUT')
        <input type="hidden" name="theme_key" id="selectedThemeKey" value="{{ $setting->theme_key }}">
        <input type="hidden" name="font_key" id="selectedFontKey" value="{{ $setting->font_key }}">
        <input type="hidden" name="font_size" id="selectedFontSize" value="{{ $setting->font_size }}">

        <div class="card border-0 shadow-sm mb-4 settings-card">
            <div class="card-header py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-display me-2 text-primary"></i>Display &amp; appearance</h6>
            </div>
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3 pb-3 border-bottom settings-mode-row">
                    <div>
                        <div class="fw-semibold">Follow your device</div>
                        <div class="small text-muted">Automatically match your computer or phone's light and dark mode.</div>
                    </div>
                    <div class="ms-md-auto flex-shrink-0">
                        <input class="settings-toggle" type="checkbox" role="switch" id="followSystemSwitch"
                               name="follow_system" value="1" {{ $setting->follow_system ? 'checked' : '' }}
                               aria-label="Follow device color mode">
                    </div>
                </div>
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
                    <div>
                        <div class="fw-semibold">Use dark mode</div>
                        <div class="small text-muted">Keep RANIAG in dark mode regardless of your device setting.</div>
                    </div>
                    <div class="ms-md-auto flex-shrink-0">
                        <input class="settings-toggle" type="checkbox" role="switch" id="darkModeSwitch"
                               name="dark_mode" value="1" {{ $setting->dark_mode && ! $setting->follow_system ? 'checked' : '' }}
                               aria-label="Use dark mode">
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="small fw-semibold text-muted d-block mb-2">Font</label>
                        <div class="row g-2">
                            @foreach ($fonts as $key => $font)
                                <div class="col-6 col-xl-3">
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
                        <div class="type-preview mb-3" id="typePreview" style="font-size: {{ $fontSizes[$setting->font_size]['root_px'] ?? '16px' }};">
                            <div class="type-preview-kicker">Sample</div>
                            <div class="type-preview-title">Incident report</div>
                            <p class="type-preview-body mb-0">Tracking number, barangay, and status stay at this size after you save.</p>
                        </div>
                        <input type="range" class="form-range font-size-slider" id="fontSizeSlider"
                               min="0" max="{{ count($fontSizeKeys) - 1 }}" step="1" value="{{ $fontSizeIndex }}"
                               aria-valuetext="{{ $fontSizes[$setting->font_size]['label'] ?? 'Default' }}">
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
                                    class="theme-swatch-btn w-100 border rounded-3 p-2 text-center position-relative {{ $setting->theme_key === $key ? 'selected' : '' }}"
                                    data-theme-key="{{ $key }}"
                                    onclick="selectTheme('{{ $key }}')">
                                <i class="bi bi-check-circle-fill theme-swatch-check position-absolute top-0 end-0 m-2"></i>
                                <span class="theme-mini" aria-hidden="true">
                                    <span class="theme-mini-side" style="background: {{ $preset['vars']['--raniag-sidebar'] }};"></span>
                                    <span class="theme-mini-main" style="background: {{ $preset['vars']['--raniag-surface'] }};">
                                        <span class="theme-mini-line" style="background: {{ $preset['vars']['--raniag-border'] }};"></span>
                                        <span class="theme-mini-btn" style="background: {{ $preset['swatch'] }};"></span>
                                    </span>
                                </span>
                                <div class="small fw-semibold mt-2">{{ $preset['label'] }}</div>
                            </button>
                        </div>
                    @endforeach
                </div>
                <p class="small text-muted mb-0 mt-3">Your color theme carries through navigation, buttons, and key interface accents in both color modes.</p>
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
                        <input class="settings-toggle" id="pushPermissionSwitch" type="checkbox" role="switch" aria-label="Push notifications">
                    </div>
                </div>
                <div id="pushNotifStatus" class="small mt-3 mb-0 text-muted">Notifications are currently off.</div>
                <div id="pushAlertOptions" class="dropdown mt-3 d-none">
                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                        Alert options
                    </button>
                    <div class="dropdown-menu p-3 shadow-sm" style="min-width: 220px;">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="pushSoundSwitch" checked>
                            <label class="form-check-label" for="pushSoundSwitch">Sound</label>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" id="pushVibrateSwitch" checked>
                            <label class="form-check-label" for="pushVibrateSwitch">Vibrate</label>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm mt-3 d-none" id="pushTestBtn">Send test notification</button>
            </div>
        </div>

        <div class="settings-actions">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save Settings</button>
            <button type="submit" form="resetThemeForm" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset to Default</button>
        </div>
    </form>

    <form method="POST" action="{{ route('settings.appearance.reset') }}" id="resetThemeForm" class="d-none">
        @csrf
    </form>
    </main>

@push('styles')
<style>
    .system-settings-page {
        --settings-ink: #163244;
        --settings-muted: #637987;
        max-width: 1160px;
        margin: 0 auto;
    }

    .settings-hero {
        position: relative;
        isolation: isolate;
        display: flex;
        align-items: end;
        justify-content: space-between;
        gap: 2rem;
        min-height: 220px;
        overflow: hidden;
        margin: 0 0 1.5rem;
        padding: clamp(1.5rem, 4vw, 2.7rem);
        border: 1px solid rgba(202, 229, 224, 0.16);
        border-radius: 1.25rem;
        background:
            radial-gradient(ellipse at 85% 5%, rgba(94, 212, 176, 0.25), transparent 33%),
            radial-gradient(ellipse at 77% 125%, rgba(234, 192, 111, 0.2), transparent 39%),
            linear-gradient(118deg, #102d3c 0%, #123f4b 58%, #0a625f 100%);
        color: #f1f7f4;
        box-shadow: 0 1.2rem 2.8rem rgba(16, 45, 60, 0.13);
    }

    .settings-hero > div:first-child,
    .settings-hero-meta { position: relative; z-index: 1; }
    .settings-eyebrow {
        display: block;
        margin: 0 0 0.6rem;
        color: #76dfbf;
        font-size: 0.66rem;
        font-weight: 800;
        letter-spacing: 0.14em;
    }
    .settings-hero h1 {
        margin: 0;
        color: #f1f7f4;
        font-size: clamp(2rem, 4vw, 3.15rem);
        font-weight: 750;
        letter-spacing: -0.055em;
        line-height: 0.98;
    }
    .settings-hero h1 em { color: #7fe0c1; font-style: normal; }
    .settings-hero p { max-width: 58ch; margin: 0.75rem 0 0; color: #c4d8dc; line-height: 1.6; }
    .settings-hero-meta { display: flex; flex-direction: column; align-items: flex-end; gap: 0.6rem; flex: 0 0 auto; }
    .settings-date,
    .settings-private {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.65rem 0.85rem;
        border: 1px solid rgba(226, 246, 240, 0.2);
        border-radius: 99px;
        background: rgba(4, 23, 33, 0.24);
        color: #e2efed;
        font-size: 0.76rem;
        backdrop-filter: blur(10px);
    }
    .settings-private { color: #b6e6d7; }
    .settings-orbit {
        position: absolute;
        z-index: -1;
        width: 310px;
        height: 190px;
        border: 1px solid rgba(145, 224, 202, 0.18);
        border-radius: 50%;
        transform: rotate(-32deg);
    }
    .settings-orbit-one { right: 4%; top: -43%; }
    .settings-orbit-two { right: -2%; top: -34%; width: 370px; height: 235px; border-color: rgba(233, 199, 127, 0.2); transform: rotate(24deg); }

    .system-settings-form { display: grid; gap: 1.25rem; }
    .system-settings-form > .settings-card { margin-bottom: 0 !important; }

    .system-settings-form .settings-card {
        background-color: var(--raniag-card, #fff);
        border: 1px solid var(--raniag-border) !important;
        border-radius: 1rem;
        box-shadow: var(--raniag-card-shadow, 0 0.7rem 1.8rem rgba(15, 23, 42, 0.055)) !important;
    }

    .system-settings-form .settings-card .card-header {
        background-color: transparent;
        border-bottom: 1px solid var(--raniag-border);
        padding: 1rem 1.25rem;
    }

    .system-settings-form .settings-card .card-body {
        padding: 1.25rem;
    }

    .settings-mode-row {
        border-color: var(--raniag-border) !important;
    }

    .theme-swatch-btn,
    .font-swatch-btn {
        background-color: var(--raniag-card, #fff);
        color: inherit;
        border-color: var(--raniag-border);
        cursor: pointer;
        transition: box-shadow 0.15s ease, border-color 0.15s ease, transform 0.15s ease;
    }

    .theme-mini {
        display: flex;
        height: 4.5rem;
        border-radius: 0.5rem;
        overflow: hidden;
        border: 1px solid var(--raniag-border);
    }

    .theme-mini-side { width: 28%; }
    .theme-mini-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 0.4rem;
        padding: 0.55rem;
    }
    .theme-mini-line { height: 6px; border-radius: 99px; width: 80%; }
    .theme-mini-btn { height: 10px; width: 46%; border-radius: 99px; }

    .type-preview {
        border: 1px solid var(--raniag-border);
        border-radius: 0.75rem;
        padding: 0.9rem 1rem;
        background: var(--raniag-card, #fff);
        line-height: 1.35;
    }
    .type-preview-kicker {
        font-size: 0.72em;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: var(--raniag-primary);
        font-weight: 700;
    }
    .type-preview-title { font-size: 1.15em; font-weight: 700; margin: 0.15rem 0; }
    .type-preview-body { font-size: 0.92em; color: #64748b; }
    [data-theme="dark"] .type-preview-body { color: #94a3b8; }

    .theme-swatch-btn:disabled {
        opacity: 0.45;
        cursor: not-allowed;
        transform: none;
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

    .font-swatch-btn.selected {
        border-color: var(--raniag-primary) !important;
        box-shadow: 0 0 0 0.2rem var(--raniag-primary-light);
    }
    .font-swatch-btn {
        min-height: 5.4rem;
        border-radius: 0.8rem !important;
    }
    .font-swatch-preview { font-size: 1.35rem; font-weight: 700; }

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

    .system-settings-form .card-header h6 { color: var(--raniag-ink, inherit); }
    .system-settings-form .card-header h6 > i { color: var(--raniag-primary) !important; }
    .system-settings-form .card-body > .d-flex { min-height: 3.25rem; }
    .system-settings-form .text-muted { color: var(--raniag-muted, #64748b) !important; }
    .system-settings-form .settings-toggle { cursor: pointer; }
    .system-settings-form .theme-swatch-btn { min-height: 7rem; border-radius: 0.85rem !important; }
    .system-settings-form .theme-mini { height: 3.8rem; }
    .system-settings-form #pushNotifStatus {
        display: inline-flex;
        align-items: center;
        min-height: 2rem;
        padding: 0.25rem 0.65rem;
        border-radius: 99px;
        background: var(--raniag-primary-light);
    }
    .settings-actions {
        position: sticky;
        z-index: 5;
        bottom: 0;
        display: flex;
        flex-wrap: wrap;
        gap: 0.65rem;
        padding: 0.85rem 1rem;
        border: 1px solid var(--raniag-border);
        border-radius: 1rem;
        background: var(--raniag-card, #fff);
        box-shadow: 0 0.65rem 1.8rem rgba(15, 23, 42, 0.12);
    }
    .settings-actions .btn { min-height: 2.7rem; padding-inline: 1rem; }
    @media (max-width: 767.98px) {
        .settings-hero { min-height: 230px; flex-direction: column; align-items: flex-start; }
        .settings-hero-meta { align-items: flex-start; }
        .system-settings-form .settings-card .card-header,
        .system-settings-form .settings-card .card-body { padding-left: 1rem; padding-right: 1rem; }
        .settings-actions { position: static; }
    }
</style>
@endpush

@push('scripts')
<script>
    const THEME_VARS = @json(collect($presets)->map(fn ($p) => $p['vars']));
    const THEME_DARK = @json(collect(array_keys($presets))->mapWithKeys(fn ($key) => [$key => \App\Support\ThemePresets::darkPalette($key)]));
    const FONT_SIZE_KEYS = @json($fontSizeKeys);
    const FONT_SIZE_LABELS = @json(collect($fontSizes)->map(fn ($s) => $s['label']));
    const FONT_SIZE_ROOT_PX = @json(collect($fontSizes)->map(fn ($s) => $s['root_px']));

    const themeButtons = [...document.querySelectorAll('.theme-swatch-btn')];
    const fontButtons = [...document.querySelectorAll('.font-swatch-btn')];
    const darkModeSwitch = document.getElementById('darkModeSwitch');
    const followSystemSwitch = document.getElementById('followSystemSwitch');
    const systemColorMode = window.matchMedia('(prefers-color-scheme: dark)');
    const fontSizeSlider = document.getElementById('fontSizeSlider');
    const fontSizeCurrentLabel = document.getElementById('fontSizeCurrentLabel');
    const fontSizeTicks = [...document.querySelectorAll('.font-size-ticks span')];
    const typePreview = document.getElementById('typePreview');

    function applyPreview(themeKey, darkMode, followSystem) {
        const vars = THEME_VARS[themeKey];
        if (!vars) return;
        const root = document.documentElement;
        Object.entries(vars).forEach(([name, value]) => root.style.setProperty(name, value));
        const useDarkMode = followSystem ? systemColorMode.matches : darkMode;
        root.style.setProperty('color-scheme', useDarkMode ? 'dark' : 'light');
        if (useDarkMode) {
            const dark = THEME_DARK[themeKey] || THEME_DARK.ocean;
            root.style.setProperty('--raniag-surface', dark.surface);
            root.style.setProperty('--raniag-border', dark.border);
            root.style.setProperty('--raniag-card', dark.card);
            root.style.setProperty('--raniag-card-raised', dark.raised);
            root.style.setProperty('--raniag-ink', '#e8eef6');
            root.style.setProperty('--raniag-muted', '#c5d4e4');
            root.style.setProperty('--bs-secondary-color', '#c5d4e4');
            root.style.setProperty('--raniag-primary-light', 'rgba(255, 255, 255, 0.08)');
            root.setAttribute('data-theme', 'dark');
        } else {
            root.style.setProperty('--raniag-ink', '#0f172a');
            root.style.setProperty('--raniag-muted', '#5c6b7a');
            root.style.setProperty('--raniag-card', '#ffffff');
            root.style.setProperty('--raniag-card-raised', '#f8fafc');
            root.style.setProperty('--raniag-card-shadow', '0 0.5rem 1.5rem rgba(15, 23, 42, 0.08)');
            root.style.setProperty('--bs-body-color', '#1e293b');
            root.style.setProperty('--bs-secondary-color', '#5c6b7a');
            root.style.setProperty('--bs-emphasis-color', '#0f172a');
            root.style.setProperty('--bs-body-bg', (vars && vars['--raniag-surface']) || '#f4f7fb');
            root.style.setProperty('--bs-secondary-bg', '#ffffff');
            root.style.setProperty('--bs-tertiary-bg', '#f8fafc');
            root.setAttribute('data-theme', 'light');
        }
    }

    function syncThemeAvailability() {
        applyPreview(document.getElementById('selectedThemeKey').value, darkModeSwitch.checked, followSystemSwitch.checked);
    }

    function selectTheme(key) {
        document.getElementById('selectedThemeKey').value = key;
        themeButtons.forEach((btn) => btn.classList.toggle('selected', btn.dataset.themeKey === key));
        applyPreview(key, darkModeSwitch.checked, followSystemSwitch.checked);
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
        if (typePreview) typePreview.style.fontSize = FONT_SIZE_ROOT_PX[key] || '16px';
        fontSizeSlider.setAttribute('aria-valuetext', FONT_SIZE_LABELS[key] || key);
    }

    fontSizeSlider.addEventListener('input', function () {
        applyFontSizeByIndex(parseInt(this.value, 10));
    });

    followSystemSwitch.addEventListener('change', function () {
        if (followSystemSwitch.checked) darkModeSwitch.checked = false;
        syncThemeAvailability();
    });
    darkModeSwitch.addEventListener('change', function () {
        if (darkModeSwitch.checked) followSystemSwitch.checked = false;
        syncThemeAvailability();
    });
    systemColorMode.addEventListener('change', function () {
        if (followSystemSwitch.checked) syncThemeAvailability();
    });
    syncThemeAvailability();

    themeButtons.forEach((btn) => {
        btn.classList.toggle('selected', btn.dataset.themeKey === '{{ $setting->theme_key }}');
    });
</script>
@endpush
</x-app-layout>
