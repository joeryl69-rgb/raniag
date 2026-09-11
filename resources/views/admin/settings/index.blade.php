<x-app-layout>
    <x-slot name="header">
        {{ __('System Settings') }}
    </x-slot>

    <p class="small text-muted mb-3">Pick a color theme and light/dark mode for the whole system. Changes apply to every account immediately — no exceptions.</p>

    <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf
        @method('PUT')
        <input type="hidden" name="theme_key" id="selectedThemeKey" value="{{ $setting->theme_key }}">

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
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

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-moon-stars-fill me-2 text-primary"></i>Appearance</h6>
            </div>
            <div class="card-body">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="darkModeSwitch" name="dark_mode" value="1" {{ $setting->dark_mode ? 'checked' : '' }}>
                    <label class="form-check-label" for="darkModeSwitch">Dark mode</label>
                </div>
                <div class="form-text">Applies on top of the selected color theme.</div>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save Theme</button>
            <button type="submit" form="resetThemeForm" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset to Default</button>
        </div>
    </form>

    <form method="POST" action="{{ route('admin.settings.reset') }}" id="resetThemeForm" class="d-none">
        @csrf
    </form>

@push('styles')
<style>
    .theme-swatch-btn {
        background-color: #fff;
        cursor: pointer;
        transition: box-shadow 0.15s ease, border-color 0.15s ease;
    }
    .theme-swatch-btn.selected,
    .theme-swatch-btn:hover {
        border-color: var(--raniag-primary) !important;
        box-shadow: 0 0 0 0.2rem var(--raniag-primary-light);
    }
</style>
@endpush

@push('scripts')
<script>
    const THEME_VARS = @json(collect($presets)->map(fn ($p) => $p['vars']));

    function applyPreview(themeKey, darkMode) {
        const vars = THEME_VARS[themeKey];
        if (!vars) return;
        const root = document.documentElement;
        Object.entries(vars).forEach(([name, value]) => root.style.setProperty(name, value));
        if (darkMode) {
            root.style.setProperty('--raniag-surface', '#0f172a');
            root.style.setProperty('--raniag-border', '#253449');
        }
    }

    function selectTheme(key) {
        document.getElementById('selectedThemeKey').value = key;
        document.querySelectorAll('.theme-swatch-btn').forEach((btn) => {
            btn.classList.toggle('selected', btn.dataset.themeKey === key);
        });
        applyPreview(key, document.getElementById('darkModeSwitch').checked);
    }

    document.getElementById('darkModeSwitch').addEventListener('change', function () {
        applyPreview(document.getElementById('selectedThemeKey').value, this.checked);
    });
</script>
@endpush
</x-app-layout>
