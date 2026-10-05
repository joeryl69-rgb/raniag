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
                        <div class="fw-semibold">Dark mode</div>
                        <div class="small text-muted">Dark surfaces on every page. Color themes stay locked until you turn this off.</div>
                    </div>
                    <div class="ms-md-auto flex-shrink-0">
                        <input class="settings-toggle" type="checkbox" role="switch" id="darkModeSwitch"
                               name="dark_mode" value="1" {{ $setting->dark_mode ? 'checked' : '' }}
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
                                    onclick="selectTheme('{{ $key }}')"
                                    @disabled($setting->dark_mode)>
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
                <p class="small text-muted mb-0 mt-3" id="themeDisabledNote" @if(! $setting->dark_mode) hidden @endif>Color themes are off while dark mode is on. Turn dark mode off to choose a theme.</p>
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
                    <div class="ms-md-auto flex-shrink-0 d-flex gap-2">
                        <button type="button" class="btn btn-primary btn-sm" id="pushEnableBtn">Enable on this browser</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm d-none" id="pushDisableBtn">Turn off</button>
                    </div>
                </div>
                <div id="pushNotifStatus" class="small mt-3 mb-0 text-muted">Notifications are currently off.</div>
                <button type="button" class="btn btn-outline-primary btn-sm mt-3 d-none" id="pushTestBtn">Send test notification</button>
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

    .theme-swatch-btn,
    .font-swatch-btn {
        background-color: var(--raniag-surface, #fff);
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
        background: var(--raniag-surface, #fff);
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
    const fontSizeSlider = document.getElementById('fontSizeSlider');
    const fontSizeCurrentLabel = document.getElementById('fontSizeCurrentLabel');
    const fontSizeTicks = [...document.querySelectorAll('.font-size-ticks span')];
    const typePreview = document.getElementById('typePreview');

    function applyPreview(themeKey, darkMode) {
        const vars = THEME_VARS[themeKey];
        if (!vars) return;
        const root = document.documentElement;
        Object.entries(vars).forEach(([name, value]) => root.style.setProperty(name, value));
        if (darkMode) {
            root.style.setProperty('--raniag-surface', '#0f172a');
            root.style.setProperty('--raniag-border', '#253449');
            root.style.setProperty('--raniag-primary-light', 'rgba(255, 255, 255, 0.06)');
            root.setAttribute('data-theme', 'dark');
        } else {
            root.setAttribute('data-theme', 'light');
        }
    }

    function syncThemeAvailability() {
        const dark = darkModeSwitch.checked;
        themeButtons.forEach((btn) => { btn.disabled = dark; });
        const note = document.getElementById('themeDisabledNote');
        if (note) note.hidden = !dark;
        applyPreview(document.getElementById('selectedThemeKey').value, dark);
    }

    function selectTheme(key) {
        if (darkModeSwitch.checked) return;
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
        if (typePreview) typePreview.style.fontSize = FONT_SIZE_ROOT_PX[key] || '16px';
        fontSizeSlider.setAttribute('aria-valuetext', FONT_SIZE_LABELS[key] || key);
    }

    fontSizeSlider.addEventListener('input', function () {
        applyFontSizeByIndex(parseInt(this.value, 10));
    });

    darkModeSwitch.addEventListener('change', syncThemeAvailability);
    syncThemeAvailability();

    themeButtons.forEach((btn) => {
        btn.classList.toggle('selected', btn.dataset.themeKey === '{{ $setting->theme_key }}');
    });
</script>
@endpush
</x-app-layout>
