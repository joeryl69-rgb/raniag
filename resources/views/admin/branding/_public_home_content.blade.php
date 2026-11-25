@php
    $copy = old('home_content', $homeContent);
    if (! is_array($copy)) {
        $copy = $homeContent;
    }
    $savedHeroImagePath = data_get($homeContent, 'hero.background_image_path');
    $savedHeroImageUrl = $savedHeroImagePath && \Illuminate\Support\Facades\Storage::disk('public')->exists($savedHeroImagePath)
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($savedHeroImagePath)
        : asset('images/pamplona-landmark.jpg');
    $copyGroups = [
        [
            'title' => 'Public header, navigation, and footer',
            'key' => 'shell',
            'description' => 'These labels appear in the shared public site shell. Their destinations remain fixed.',
            'fields' => [
                ['shell.home_title', 'Home page browser title', 120],
                ['shell.meta_description', 'Search description', 180],
                ['shell.social_description', 'Social sharing description', 240],
                ['shell.public_reporting_label', 'Top strip label', 80],
                ['shell.navigation.home', 'Home link', 80],
                ['shell.navigation.track', 'Track report link', 80],
                ['shell.navigation.live_map', 'Live map link', 80],
                ['shell.navigation.community', 'Community dashboard link', 80],
                ['shell.navigation.staff', 'Staff dashboard link', 80],
                ['shell.navigation.staff_login', 'Staff login link', 80],
                ['shell.navigation.theme', 'Theme control label', 80],
                ['shell.navigation.report', 'Report button', 80],
                ['shell.footer.desk_label', 'Footer desk label', 100],
                ['shell.footer.community_label', 'Footer community label', 100],
                ['shell.footer.description', 'Footer system description', 500, 'textarea'],
                ['shell.footer.report_channel_label', 'Footer reporting label', 120],
                ['shell.footer.default_report_text', 'Default footer reporting message', 500, 'textarea'],
                ['shell.footer.report_section', 'Footer report group heading', 80],
                ['shell.footer.file_report', 'Footer file-report link', 80],
                ['shell.footer.track_report', 'Footer tracking link', 80],
                ['shell.footer.how_it_works', 'Footer how-it-works link', 80],
                ['shell.footer.access_section', 'Footer access group heading', 80],
                ['shell.footer.staff_dashboard', 'Footer staff dashboard link', 80],
                ['shell.footer.staff_login', 'Footer staff login link', 80],
                ['shell.footer.replay_tour', 'Footer tour link', 80],
                ['shell.footer.support', 'Footer support link', 80],
                ['shell.footer.hotline_label', 'Emergency hotline label', 80],
            ],
        ],
        [
            'title' => 'Home introduction, shortcuts, and report QR',
            'key' => 'home',
            'description' => 'The visual layout stays intact. Headline emphasis is controlled by its separate accent-line field.',
            'fields' => [
                ['hero.eyebrow', 'Hero organization line', 120],
                ['hero.desk_label', 'Hero desk label', 100],
                ['hero.title_lead', 'Hero headline', 140],
                ['hero.title_emphasis', 'Hero accented headline line', 120],
                ['hero.tagline', 'Hero tagline', 180],
                ['hero.description', 'Hero description', 500, 'textarea'],
                ['hero.report_button', 'Report button', 80],
                ['hero.track_button', 'Track button', 80],
                ['hero.latitude', 'Map latitude label', 40],
                ['hero.longitude', 'Map longitude label', 40],
                ['hero.location_label', 'Map location label', 120],
                ['shortcuts.0.kicker', 'Shortcut 1 eyebrow', 80],
                ['shortcuts.0.title', 'Shortcut 1 title', 100],
                ['shortcuts.1.kicker', 'Shortcut 2 eyebrow', 80],
                ['shortcuts.1.title', 'Shortcut 2 title', 100],
                ['shortcuts.2.kicker', 'Shortcut 3 eyebrow', 80],
                ['shortcuts.2.title', 'Shortcut 3 title', 100],
                ['qr.eyebrow', 'QR section eyebrow', 80],
                ['qr.stage', 'QR section label', 80],
                ['qr.title', 'QR heading', 140],
                ['qr.description', 'QR instructions', 1000, 'textarea'],
                ['qr.link_label', 'QR form link', 80],
                ['qr.download_label', 'QR download button', 80],
                ['qr.code_alt', 'QR code description for screen readers', 180],
            ],
        ],
        [
            'title' => 'Field guide and reporting steps',
            'key' => 'journey',
            'description' => 'The four-step guide, illustrations, and destinations stay in their original places.',
            'fields' => [
                ['journey.eyebrow', 'Guide eyebrow', 100],
                ['journey.sequence', 'Guide sequence label', 40],
                ['journey.title_lead', 'Guide headline', 140],
                ['journey.title_emphasis', 'Guide accented headline line', 120],
                ['journey.description', 'Guide introduction', 600, 'textarea'],
                ['journey.visual_label', 'Guide visual label', 100],
                ['journey.guide_label', 'Mascot label', 100],
                ['journey.guide_alt', 'Mascot image description', 180],
                ['journey.tour_label', 'Tour button label', 120],
                ['journey.tour_hint', 'Tour button helper text', 120],
                ['journey.steps.0.eyebrow', 'Step 1 eyebrow', 100],
                ['journey.steps.0.title', 'Step 1 title', 140],
                ['journey.steps.0.description', 'Step 1 description', 600, 'textarea'],
                ['journey.steps.0.link_label', 'Step 1 link label', 80],
                ['journey.steps.0.visual_status', 'Step 1 visual status', 80],
                ['journey.steps.0.visual_note', 'Step 1 guide note', 250],
                ['journey.steps.1.eyebrow', 'Step 2 eyebrow', 100],
                ['journey.steps.1.title', 'Step 2 title', 140],
                ['journey.steps.1.description', 'Step 2 description', 600, 'textarea'],
                ['journey.steps.1.link_label', 'Step 2 link label', 80],
                ['journey.steps.1.visual_status', 'Step 2 visual status', 80],
                ['journey.steps.1.visual_note', 'Step 2 guide note', 250],
                ['journey.steps.2.eyebrow', 'Step 3 eyebrow', 100],
                ['journey.steps.2.title', 'Step 3 title', 140],
                ['journey.steps.2.description', 'Step 3 description', 600, 'textarea'],
                ['journey.steps.2.link_label', 'Step 3 link label', 80],
                ['journey.steps.2.visual_status', 'Step 3 visual status', 80],
                ['journey.steps.2.visual_note', 'Step 3 guide note', 250],
                ['journey.steps.3.eyebrow', 'Step 4 eyebrow', 100],
                ['journey.steps.3.title', 'Step 4 title', 140],
                ['journey.steps.3.description', 'Step 4 description', 600, 'textarea'],
                ['journey.steps.3.link_label', 'Step 4 link label', 80],
                ['journey.steps.3.visual_status', 'Step 4 visual status', 80],
                ['journey.steps.3.visual_note', 'Step 4 guide note', 250],
            ],
        ],
        [
            'title' => 'Public updates and FAQ heading',
            'key' => 'briefing',
            'description' => 'Announcement posts remain managed in Announcements; edit their section labels and the empty-state copy here.',
            'fields' => [
                ['briefing.eyebrow', 'Public information eyebrow', 100],
                ['briefing.sequence', 'Public information sequence', 40],
                ['briefing.title_lead', 'Public information headline', 140],
                ['briefing.title_emphasis', 'Public information accented line', 120],
                ['briefing.description', 'Public information introduction', 500, 'textarea'],
                ['briefing.announcements.badge', 'Updates badge', 80],
                ['briefing.announcements.local_desk', 'Local desk label', 80],
                ['briefing.announcements.title', 'Updates heading', 140],
                ['briefing.announcements.empty_title', 'No-updates heading', 140],
                ['briefing.announcements.empty_description', 'No-updates description', 300, 'textarea'],
                ['briefing.faq.badge', 'FAQ badge', 80],
                ['briefing.faq.title', 'FAQ heading', 140],
                ['briefing.faq.support_link', 'FAQ support link label', 100],
            ],
        ],
        [
            'title' => 'App promotion and human support',
            'key' => 'app',
            'description' => 'The install action and support destination keep their existing behavior.',
            'fields' => [
                ['app.aside_label', 'Support sidebar description', 180],
                ['app.field_tool_label', 'App card label', 80],
                ['app.index', 'App section index', 40],
                ['app.title', 'App heading', 160],
                ['app.description', 'App description', 300, 'textarea'],
                ['app.download_button', 'App download button', 80],
                ['app.illustration_alt', 'App illustration description', 180],
                ['app.install_success', 'Install success message', 240],
                ['app.install_cancelled', 'Install cancelled message', 300],
                ['app.already_installed', 'Already-installed message', 240],
                ['app.install_instructions', 'Install instructions', 300],
                ['support.index', 'Support section index', 60],
                ['support.title', 'Support heading', 160],
                ['support.description', 'Support description', 500, 'textarea'],
                ['support.button', 'Support button', 100],
            ],
        ],
    ];
    $faqs = data_get($copy, 'faqs', []);
    $faqs = is_array($faqs) ? array_values(array_filter($faqs, 'is_array')) : [];
    if ($faqs === []) {
        $faqs = [config('public_home.faqs.0')];
    }
@endphp

<section class="card branding-settings-card mb-4">
    <div class="card-header py-3">
        <h2 class="h6 fw-bold mb-1"><i class="bi bi-window-sidebar me-2 text-primary"></i>Public site content</h2>
        <p class="small text-muted mb-0">Customize the public home page, navigation labels, FAQs, and footer without changing its layout or working links.</p>
    </div>
    <div class="card-body">
        <p class="small text-muted mb-3">Enter the words you want visitors to see.</p>
        <p class="small text-muted mb-3" data-copy-js-only hidden>Click any text field to see the live-detail picker beside it. The homepage layout and links stay in place.</p>
        <nav class="branding-home-map mb-4" aria-label="Public homepage layout" data-copy-home-map hidden>
            <div class="branding-home-map-heading">
                <div>
                    <span class="text-uppercase small fw-bold text-primary">Homepage layout</span>
                    <h3 class="h6 fw-bold mb-1">Where each editable section appears</h3>
                    <p class="small text-muted mb-0">Choose a section to jump to its settings. This map follows the public page from top to bottom.</p>
                </div>
            </div>
            <div class="branding-home-map-frame mb-2">
                <span>Top of every page</span>
                <a href="#home-copy-group-shell" data-copy-map-link="shell">Header and navigation</a>
            </div>
            <a class="branding-home-map-block mb-2" href="#home-copy-group-home" data-copy-map-link="home">
                <span class="branding-home-map-index">01</span>
                <span><strong>Introduction, shortcuts, and report QR</strong><small>First homepage section</small></span>
            </a>
            <a class="branding-home-map-block mb-2" href="#home-copy-group-journey" data-copy-map-link="journey">
                <span class="branding-home-map-index">02</span>
                <span><strong>Field guide and reporting steps</strong><small>After the introduction</small></span>
            </a>
            <div class="branding-home-map-columns mb-2">
                <div class="branding-home-map-stack">
                    <a class="branding-home-map-block" href="#home-copy-group-briefing" data-copy-map-link="briefing">
                        <span class="branding-home-map-index">03</span>
                        <span><strong>Public updates and FAQ heading</strong><small>Main information column</small></span>
                    </a>
                    <a class="branding-home-map-block" href="#home-copy-group-faqs" data-copy-map-link="faqs">
                        <span class="branding-home-map-index">04</span>
                        <span><strong>Frequently asked questions</strong><small>Under the updates section</small></span>
                    </a>
                </div>
                <a class="branding-home-map-block" href="#home-copy-group-app" data-copy-map-link="app">
                    <span class="branding-home-map-index">05</span>
                    <span><strong>App promotion and support</strong><small>Beside updates on desktop; below on mobile</small></span>
                </a>
            </div>
            <div class="branding-home-map-frame">
                <span>Bottom of every page</span>
                <a href="#home-copy-group-shell" data-copy-map-link="shell">Footer information</a>
            </div>
        </nav>
        <div class="branding-copy-toolbar mb-3" data-copy-toolbar hidden>
            <label for="branding-copy-detail" class="form-label fw-semibold mb-1">Insert a live detail</label>
            <div class="d-flex flex-column flex-sm-row gap-2">
                <select id="branding-copy-detail" class="form-select" data-copy-token-select>
                    @foreach ($homePersonalizations as $key => $personalization)
                        <option value="{{ $key }}" data-current-value="{{ $personalization['value'] }}" data-source="{{ $personalization['source'] }}">{{ $personalization['label'] }}</option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-outline-primary flex-shrink-0" data-copy-token-insert disabled>
                    <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>Insert into selected field
                </button>
            </div>
            <div class="small text-muted mt-2" data-copy-detail-info aria-live="polite"></div>
            <div class="small text-muted mt-2" data-copy-active aria-live="polite">Choose a text field to begin.</div>
        </div>

        @foreach ($copyGroups as $group)
            <details id="home-copy-group-{{ $group['key'] }}" class="branding-copy-group" data-copy-group="{{ $group['key'] }}" @if ($loop->first) open @endif>
                <summary>
                    <span>{{ $group['title'] }}</span>
                    <span class="small text-muted fw-normal">{{ count($group['fields']) }} fields</span>
                </summary>
                <div class="branding-copy-group-body">
                    <p class="small text-muted">{{ $group['description'] }}</p>
                    <div class="row g-3">
                        @foreach ($group['fields'] as $field)
                            @php
                                $fieldId = 'home-copy-' . str_replace('.', '-', $field[0]);
                                $fieldName = 'home_content[' . str_replace('.', '][', $field[0]) . ']';
                                $fieldValue = data_get($copy, $field[0], '');
                                $fieldValue = is_scalar($fieldValue) ? (string) $fieldValue : '';
                                $fieldType = $field[3] ?? 'text';
                            @endphp
                            <div class="col-12 {{ $fieldType === 'textarea' ? '' : 'col-md-6' }}">
                                @include('admin.branding._copy_editor', [
                                    'fieldId' => $fieldId,
                                    'fieldName' => $fieldName,
                                    'fieldValue' => $fieldValue,
                                    'fieldLabel' => $field[1],
                                    'fieldMaxLength' => $field[2],
                                    'fieldMultiline' => $fieldType === 'textarea',
                                    'fieldRequired' => true,
                                ])
                            </div>
                        @endforeach
                    </div>
                    @if ($group['key'] === 'home')
                        <fieldset class="mt-4 p-3 border rounded-3" data-hero-image-controls>
                            <legend class="float-none w-auto px-2 fs-6 fw-bold">Homepage municipality background</legend>
                            <p class="small text-muted">Upload a JPG, PNG, or WebP image up to 8 MB. Adjust the focal point to choose which part remains visible as the hero resizes.</p>
                            <div class="row g-3 align-items-start">
                                <div class="col-12 col-lg-7">
                                    <div class="position-relative border rounded-3 overflow-hidden bg-dark" style="aspect-ratio:16/7;">
                                        <img src="{{ $savedHeroImageUrl }}" alt="Preview of the homepage municipality background" class="w-100 h-100" style="object-fit:cover;object-position:{{ data_get($copy, 'hero.background_position_x', 50) }}% {{ data_get($copy, 'hero.background_position_y', 54) }}%;" data-hero-preview-image>
                                        <div class="position-absolute top-0 start-0 w-100 h-100" style="pointer-events:none;background:linear-gradient(90deg,rgba(7,20,31,.96),rgba(7,20,31,.32) 72%,rgba(7,20,31,.12));" data-hero-preview-overlay></div>
                                    </div>
                                    <div class="form-text">The dark overlay protects headline contrast on both desktop and mobile.</div>
                                </div>
                                <div class="col-12 col-lg-5">
                                    <label for="hero_background_image" class="form-label fw-semibold">Replace background image</label>
                                    <input id="hero_background_image" name="hero_background_image" type="file" class="form-control" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-hero-image-input>
                                    @error('hero_background_image')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                    @if ($savedHeroImagePath)
                                        <label class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" name="remove_hero_background_image" value="1" @checked(old('remove_hero_background_image'))>
                                            <span class="form-check-label">Use the default municipality image</span>
                                        </label>
                                    @endif
                                    <div class="mt-3">
                                        <label for="hero_background_position_x" class="form-label fw-semibold">Horizontal focal point <output data-hero-value="x">{{ data_get($copy, 'hero.background_position_x', 50) }}%</output></label>
                                        <input id="hero_background_position_x" type="range" class="form-range" min="0" max="100" step="1" name="home_content[hero][background_position_x]" value="{{ data_get($copy, 'hero.background_position_x', 50) }}" data-hero-position-x>
                                    </div>
                                    <div>
                                        <label for="hero_background_position_y" class="form-label fw-semibold">Vertical focal point <output data-hero-value="y">{{ data_get($copy, 'hero.background_position_y', 54) }}%</output></label>
                                        <input id="hero_background_position_y" type="range" class="form-range" min="0" max="100" step="1" name="home_content[hero][background_position_y]" value="{{ data_get($copy, 'hero.background_position_y', 54) }}" data-hero-position-y>
                                    </div>
                                    <div>
                                        <label for="hero_background_overlay" class="form-label fw-semibold">Dark overlay <output data-hero-value="overlay">{{ data_get($copy, 'hero.background_overlay', 100) }}%</output></label>
                                        <input id="hero_background_overlay" type="range" class="form-range" min="40" max="100" step="1" name="home_content[hero][background_overlay]" value="{{ data_get($copy, 'hero.background_overlay', 100) }}" data-hero-overlay>
                                    </div>
                                    <input type="hidden" name="home_content[hero][background_image_path]" value="{{ data_get($homeContent, 'hero.background_image_path', '') }}">
                                </div>
                            </div>
                        </fieldset>
                    @endif
                </div>
            </details>
        @endforeach

        <details id="home-copy-group-faqs" class="branding-copy-group" data-copy-group="faqs">
            <summary>
                <span>Frequently asked questions</span>
                <span class="small text-muted fw-normal"><span data-faq-count>{{ count($faqs) }}</span> of 12</span>
            </summary>
            <div class="branding-copy-group-body">
                <p class="small text-muted">Add, remove, reorder, and edit questions and answers. The optional link label always points to the Community Dashboard.</p>
                <div class="d-grid gap-3" id="home-faq-list" data-faq-list>
                    @foreach ($faqs as $index => $faq)
                        @php
                            $faqQuestion = is_scalar($faq['question'] ?? null) ? (string) $faq['question'] : '';
                            $faqAnswer = is_scalar($faq['answer'] ?? null) ? (string) $faq['answer'] : '';
                            $faqLinkLabel = is_scalar($faq['link_label'] ?? null) ? (string) $faq['link_label'] : '';
                        @endphp
                        <div class="branding-faq-row" data-faq-row>
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                                <h3 class="h6 fw-bold mb-0">Question <span data-faq-number>{{ $index + 1 }}</span></h3>
                                <div class="d-flex gap-1">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-faq-up><i class="bi bi-arrow-up" aria-hidden="true"></i><span class="visually-hidden">Move question up</span></button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-faq-down><i class="bi bi-arrow-down" aria-hidden="true"></i><span class="visually-hidden">Move question down</span></button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-faq-remove>Remove</button>
                                </div>
                            </div>
                            <div class="mb-3">
                                @include('admin.branding._copy_editor', [
                                    'fieldId' => 'home-faq-' . $index . '-question',
                                    'fieldName' => 'home_content[faqs][' . $index . '][question]',
                                    'fieldValue' => $faqQuestion,
                                    'fieldLabel' => 'Question',
                                    'fieldMaxLength' => 180,
                                    'fieldMultiline' => false,
                                    'fieldRequired' => true,
                                    'fieldKey' => 'question',
                                ])
                            </div>
                            <div class="mb-3">
                                @include('admin.branding._copy_editor', [
                                    'fieldId' => 'home-faq-' . $index . '-answer',
                                    'fieldName' => 'home_content[faqs][' . $index . '][answer]',
                                    'fieldValue' => $faqAnswer,
                                    'fieldLabel' => 'Answer',
                                    'fieldMaxLength' => 2000,
                                    'fieldMultiline' => true,
                                    'fieldRequired' => true,
                                    'fieldKey' => 'answer',
                                ])
                            </div>
                            <div>
                                @include('admin.branding._copy_editor', [
                                    'fieldId' => 'home-faq-' . $index . '-link_label',
                                    'fieldName' => 'home_content[faqs][' . $index . '][link_label]',
                                    'fieldValue' => $faqLinkLabel,
                                    'fieldLabel' => 'Optional Community Dashboard link label',
                                    'fieldMaxLength' => 100,
                                    'fieldMultiline' => false,
                                    'fieldRequired' => false,
                                    'fieldKey' => 'link_label',
                                ])
                            </div>
                        </div>
                    @endforeach
                </div>
                <button type="button" class="btn btn-outline-primary mt-3" id="home-faq-add" data-faq-add>
                    <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Add a question
                </button>
            </div>
        </details>
    </div>
</section>

<template id="home-faq-template">
    <div class="branding-faq-row" data-faq-row>
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h3 class="h6 fw-bold mb-0">Question <span data-faq-number></span></h3>
            <div class="d-flex gap-1">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-faq-up><i class="bi bi-arrow-up" aria-hidden="true"></i><span class="visually-hidden">Move question up</span></button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-faq-down><i class="bi bi-arrow-down" aria-hidden="true"></i><span class="visually-hidden">Move question down</span></button>
                <button type="button" class="btn btn-sm btn-outline-danger" data-faq-remove>Remove</button>
            </div>
        </div>
        <div class="mb-3">
            @include('admin.branding._copy_editor', [
                'fieldId' => 'home-faq-__INDEX__-question',
                'fieldName' => 'home_content[faqs][__INDEX__][question]',
                'fieldValue' => '',
                'fieldLabel' => 'Question',
                'fieldMaxLength' => 180,
                'fieldMultiline' => false,
                'fieldRequired' => true,
                'fieldKey' => 'question',
            ])
        </div>
        <div class="mb-3">
            @include('admin.branding._copy_editor', [
                'fieldId' => 'home-faq-__INDEX__-answer',
                'fieldName' => 'home_content[faqs][__INDEX__][answer]',
                'fieldValue' => '',
                'fieldLabel' => 'Answer',
                'fieldMaxLength' => 2000,
                'fieldMultiline' => true,
                'fieldRequired' => true,
                'fieldKey' => 'answer',
            ])
        </div>
        <div>
            @include('admin.branding._copy_editor', [
                'fieldId' => 'home-faq-__INDEX__-link_label',
                'fieldName' => 'home_content[faqs][__INDEX__][link_label]',
                'fieldValue' => '',
                'fieldLabel' => 'Optional Community Dashboard link label',
                'fieldMaxLength' => 100,
                'fieldMultiline' => false,
                'fieldRequired' => false,
                'fieldKey' => 'link_label',
            ])
        </div>
    </div>
</template>

@push('scripts')
    <script>
        (() => {
            const controls = document.querySelector('[data-hero-image-controls]');
            if (!controls) return;

            const preview = controls.querySelector('[data-hero-preview-image]');
            const fileInput = controls.querySelector('[data-hero-image-input]');
            const horizontal = controls.querySelector('[data-hero-position-x]');
            const vertical = controls.querySelector('[data-hero-position-y]');
            const overlay = controls.querySelector('[data-hero-overlay]');
            const overlayPreview = controls.querySelector('[data-hero-preview-overlay]');
            let previewObjectUrl = null;

            const updatePreview = () => {
                preview.style.objectPosition = `${horizontal.value}% ${vertical.value}%`;
                controls.querySelector('[data-hero-value="x"]').value = `${horizontal.value}%`;
                controls.querySelector('[data-hero-value="y"]').value = `${vertical.value}%`;
                controls.querySelector('[data-hero-value="overlay"]').value = `${overlay.value}%`;
                overlayPreview.style.opacity = String(Number(overlay.value) / 100);
            };

            [horizontal, vertical, overlay].forEach((input) => input.addEventListener('input', updatePreview));
            fileInput.addEventListener('change', () => {
                const file = fileInput.files?.[0];
                if (!file) return;
                if (previewObjectUrl) URL.revokeObjectURL(previewObjectUrl);
                previewObjectUrl = URL.createObjectURL(file);
                preview.src = previewObjectUrl;
            });
            updatePreview();
        })();
    </script>
    <script>
        (() => {
            const form = document.querySelector('[data-copy-root]');
            const toolbar = form?.querySelector('[data-copy-toolbar]');
            const tokenSelect = toolbar?.querySelector('[data-copy-token-select]');
            const insertButton = toolbar?.querySelector('[data-copy-token-insert]');
            const activeLabel = toolbar?.querySelector('[data-copy-active]');
            const detailInfo = toolbar?.querySelector('[data-copy-detail-info]');

            if (!form || !toolbar || !tokenSelect || !insertButton || !activeLabel || !detailInfo) return;

            let activeEditor = null;
            let savedRange = null;

            const editorFrom = (target) => target instanceof Element
                ? target.closest('[data-copy-editor]')
                : null;

            const sourceFields = {
                system_name: 'system_name',
                organization: 'organization_name',
                municipality: 'municipality_label',
                province: 'province_label',
                region: 'response_area_label',
            };

            const updateDetailInfo = () => {
                const selected = tokenSelect.selectedOptions[0];
                if (!selected) return;

                const fieldName = sourceFields[tokenSelect.value];
                const field = fieldName ? form.querySelector(`[name="${fieldName}"]`) : null;
                const value = field
                    ? field.value.trim() || 'Not set'
                    : selected.dataset.currentValue || 'Not set';
                const source = selected.dataset.source || 'system settings';
                detailInfo.textContent = `Current value: ${value}. Managed in ${source}.`;
            };

            const serializeNode = (node) => {
                if (node.nodeType === Node.TEXT_NODE) return node.nodeValue || '';
                if (!(node instanceof Element)) return '';
                if (node.matches('[data-copy-token]')) return `{${node.dataset.copyToken}}`;
                if (node.tagName === 'BR') return '\n';

                let result = '';
                node.childNodes.forEach((child) => {
                    if (child instanceof Element && ['DIV', 'P'].includes(child.tagName)
                        && result && !result.endsWith('\n')) result += '\n';
                    result += serializeNode(child);
                    if (child instanceof Element && ['DIV', 'P'].includes(child.tagName)
                        && !result.endsWith('\n')) result += '\n';
                });

                return result;
            };

            const serialize = (editor) => {
                const previousEditor = activeEditor;
                activeEditor = editor;
                const value = serializeNode(editor);
                activeEditor = previousEditor;

                return value;
            };

            const updateValue = (editor) => {
                const hidden = editor.parentElement.querySelector('[data-copy-value]');
                const error = editor.parentElement.querySelector('[data-copy-error]');
                if (!hidden) return;

                hidden.value = serialize(editor);
                const requiredMissing = editor.dataset.copyRequired === 'true' && hidden.value.trim() === '';
                const tooLong = hidden.value.length > Number(editor.dataset.copyMaxlength || 0);

                if (requiredMissing || tooLong) {
                    editor.setAttribute('aria-invalid', 'true');
                    if (error) {
                        error.hidden = false;
                        error.textContent = requiredMissing
                            ? 'Please enter text for this field.'
                            : `Please keep this text within ${editor.dataset.copyMaxlength} characters.`;
                    }
                } else {
                    editor.removeAttribute('aria-invalid');
                    if (error) {
                        error.hidden = true;
                        error.textContent = '';
                    }
                }
            };

            const initializeEditor = (editor) => {
                if (editor.dataset.copyInitialized === 'true') return;

                const fallback = editor.parentElement.querySelector('[data-copy-fallback]');
                const hidden = editor.parentElement.querySelector('[data-copy-value]');
                const label = document.getElementById(editor.getAttribute('aria-labelledby'));
                if (!fallback || !hidden || !fallback.name) return;

                hidden.name = fallback.name;
                fallback.removeAttribute('name');
                fallback.removeAttribute('required');
                fallback.hidden = true;
                fallback.classList.add('d-none');
                editor.hidden = false;
                editor.classList.remove('d-none');
                editor.dataset.copyInitialized = 'true';
                if (label) label.htmlFor = editor.id;
                updateValue(editor);
            };

            const saveSelection = () => {
                const selection = window.getSelection();
                if (activeEditor && selection?.rangeCount
                    && activeEditor.contains(selection.anchorNode)) {
                    savedRange = selection.getRangeAt(0).cloneRange();
                }
            };

            const endRange = (editor) => {
                const range = document.createRange();
                range.selectNodeContents(editor);
                range.collapse(false);

                return range;
            };

            const insertNodes = (editor, nodes) => {
                let range = savedRange && editor.contains(savedRange.commonAncestorContainer)
                    ? savedRange.cloneRange()
                    : endRange(editor);
                editor.focus();
                const selection = window.getSelection();
                selection.removeAllRanges();
                selection.addRange(range);
                range.deleteContents();

                const fragment = document.createDocumentFragment();
                nodes.forEach((node) => fragment.append(node));
                const lastNode = fragment.lastChild;
                range.insertNode(fragment);
                range = document.createRange();
                if (lastNode?.parentNode) range.setStartAfter(lastNode);
                else range.selectNodeContents(editor);
                range.collapse(true);
                selection.removeAllRanges();
                selection.addRange(range);
                savedRange = range.cloneRange();
                updateValue(editor);
            };

            form.addEventListener('focusin', (event) => {
                const editor = editorFrom(event.target);
                if (!editor) return;

                activeEditor = editor;
                const field = editor.closest('[data-copy-field]');
                if (field && toolbar.parentElement !== field) field.insertBefore(toolbar, editor.nextSibling);
                toolbar.hidden = false;
                insertButton.disabled = false;
                const label = document.getElementById(editor.getAttribute('aria-labelledby'));
                activeLabel.textContent = label
                    ? `Selected field: ${label.textContent.trim()}`
                    : 'Text field selected.';
                saveSelection();
            });

            form.addEventListener('keyup', (event) => {
                if (editorFrom(event.target)) saveSelection();
            });
            form.addEventListener('mouseup', (event) => {
                if (editorFrom(event.target)) saveSelection();
            });
            document.addEventListener('selectionchange', saveSelection);

            form.addEventListener('input', (event) => {
                const editor = editorFrom(event.target);
                if (!editor) {
                    if (event.target instanceof HTMLInputElement
                        && Object.values(sourceFields).includes(event.target.name)) {
                        updateDetailInfo();
                    }

                    return;
                }

                activeEditor = editor;
                updateValue(editor);
                saveSelection();
            });

            form.addEventListener('paste', (event) => {
                const editor = editorFrom(event.target);
                if (!editor) return;

                event.preventDefault();
                let text = event.clipboardData?.getData('text/plain') || '';
                if (editor.getAttribute('aria-multiline') !== 'true') {
                    text = text.replace(/\r?\n/g, ' ');
                }
                const nodes = [];
                text.split('\n').forEach((line, index) => {
                    if (index > 0) nodes.push(document.createElement('br'));
                    if (line) nodes.push(document.createTextNode(line));
                });
                insertNodes(editor, nodes);
            });

            form.addEventListener('keydown', (event) => {
                const editor = editorFrom(event.target);
                if (!editor || event.key !== 'Enter' || event.isComposing) return;

                if (editor.getAttribute('aria-multiline') === 'true' && !(event.ctrlKey || event.metaKey)) {
                    event.preventDefault();
                    insertNodes(editor, [document.createElement('br')]);
                } else {
                    event.preventDefault();
                    form.requestSubmit();
                }
            });

            form.addEventListener('beforeinput', (event) => {
                if (editorFrom(event.target) && /^format|^insertHTML/.test(event.inputType)) {
                    event.preventDefault();
                }
            });

            form.addEventListener('drop', (event) => {
                if (editorFrom(event.target)) event.preventDefault();
            });

            insertButton.addEventListener('mousedown', (event) => event.preventDefault());
            insertButton.addEventListener('click', () => {
                if (!activeEditor) return;

                const key = tokenSelect.value;
                const label = tokenSelect.selectedOptions[0]?.textContent?.trim() || key;
                const token = document.createElement('span');
                token.className = 'branding-copy-token';
                token.contentEditable = 'false';
                token.dataset.copyToken = key;
                token.setAttribute('aria-label', `Automatic ${label}`);
                token.textContent = label;
                insertNodes(activeEditor, [token]);
                activeLabel.textContent = `${label} will update when its system setting changes.`;
            });
            tokenSelect.addEventListener('change', updateDetailInfo);

            form.addEventListener('click', (event) => {
                if (!(event.target instanceof Element)) return;

                const link = event.target.closest('[data-copy-map-link]');
                if (!link) return;

                const group = form.querySelector(`[data-copy-group="${link.dataset.copyMapLink}"]`);
                if (!group) return;

                event.preventDefault();
                group.open = true;
                group.scrollIntoView({
                    behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
                    block: 'start',
                });
            });

            form.addEventListener('branding:copy-fields-added', () => {
                form.querySelectorAll('[data-copy-editor]:not([data-copy-initialized])')
                    .forEach(initializeEditor);
            });

            form.addEventListener('submit', (event) => {
                for (const editor of form.querySelectorAll('[data-copy-editor]')) {
                    updateValue(editor);
                    if (editor.getAttribute('aria-invalid') === 'true') {
                        event.preventDefault();
                        editor.focus();
                        return;
                    }
                }
            });

            form.querySelectorAll('[data-copy-editor]').forEach(initializeEditor);
            updateDetailInfo();
            form.querySelectorAll('[data-copy-home-map]').forEach((element) => {
                element.hidden = false;
            });
            form.querySelectorAll('[data-copy-js-only]').forEach((element) => {
                element.hidden = false;
            });
        })();
    </script>
    <script>
        (() => {
            const list = document.querySelector('[data-faq-list]');
            const addButton = document.querySelector('[data-faq-add]');
            const template = document.getElementById('home-faq-template');
            const countLabel = document.querySelector('[data-faq-count]');
            const limit = 12;

            if (!list || !addButton || !template || !countLabel) return;

            const updateRows = () => {
                const rows = Array.from(list.querySelectorAll('[data-faq-row]'));
                rows.forEach((row, index) => {
                    row.querySelector('[data-faq-number]').textContent = String(index + 1);
                    row.querySelectorAll('[data-faq-key]').forEach((field) => {
                        const fieldId = `home-faq-${index}-${field.dataset.faqKey}`;
                        field.name = `home_content[faqs][${index}][${field.dataset.faqKey}]`;
                        field.id = field.hasAttribute('data-copy-fallback') ? fieldId : `${fieldId}-value`;
                        const editor = Array.from(row.querySelectorAll('[data-copy-editor]'))
                            .find((item) => item.dataset.copyEditorKey === field.dataset.faqKey);
                        if (editor) {
                            editor.id = `${fieldId}-editor`;
                            editor.setAttribute('aria-labelledby', `${editor.id}-label`);
                        }
                    });
                    row.querySelectorAll('[data-faq-label]').forEach((label) => {
                        const editor = Array.from(row.querySelectorAll('[data-copy-editor]'))
                            .find((item) => item.dataset.copyEditorKey === label.dataset.faqLabel);
                        const fallbackId = `home-faq-${index}-${label.dataset.faqLabel}`;
                        label.htmlFor = editor?.dataset.copyInitialized === 'true'
                            ? editor.id
                            : fallbackId;
                        label.id = editor ? `${editor.id}-label` : `${fallbackId}-label`;
                    });
                    row.querySelector('[data-faq-up]').disabled = index === 0;
                    row.querySelector('[data-faq-down]').disabled = index === rows.length - 1;
                    row.querySelector('[data-faq-remove]').disabled = rows.length <= 1;
                    row.querySelector('[data-faq-up]').setAttribute('aria-label', `Move question ${index + 1} up`);
                    row.querySelector('[data-faq-down]').setAttribute('aria-label', `Move question ${index + 1} down`);
                    row.querySelector('[data-faq-remove]').setAttribute('aria-label', `Remove question ${index + 1}`);
                });
                countLabel.textContent = String(rows.length);
                addButton.disabled = rows.length >= limit;
            };

            addButton.addEventListener('click', () => {
                if (list.querySelectorAll('[data-faq-row]').length >= limit) return;

                list.insertAdjacentHTML('beforeend', template.innerHTML.trim());
                updateRows();
                list.closest('form').dispatchEvent(new Event('branding:copy-fields-added'));
                list.querySelector('[data-faq-row]:last-child [data-copy-editor-key="question"]').focus();
            });

            list.addEventListener('click', (event) => {
                if (!(event.target instanceof Element)) return;

                const button = event.target.closest('[data-faq-remove], [data-faq-up], [data-faq-down]');
                if (!button) return;

                const row = button.closest('[data-faq-row]');
                if (button.matches('[data-faq-remove]')) {
                    if (list.querySelectorAll('[data-faq-row]').length <= 1) return;
                    row.remove();
                } else if (button.matches('[data-faq-up]') && row.previousElementSibling) {
                    row.previousElementSibling.before(row);
                } else if (button.matches('[data-faq-down]') && row.nextElementSibling) {
                    row.nextElementSibling.after(row);
                }
                updateRows();
            });

            updateRows();
        })();
    </script>
@endpush
