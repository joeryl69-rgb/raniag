@php
    $copy = old('home_content', $homeContent);
    if (! is_array($copy)) {
        $copy = $homeContent;
    }
    $copyGroups = [
        [
            'title' => 'Public header, navigation, and footer',
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
        <p class="form-text mb-3">Text is escaped for safety; HTML and custom URLs are not accepted. Use <code>{system_name}</code>, <code>{organization}</code>, <code>{municipality}</code>, <code>{province}</code>, <code>{region}</code>, and <code>{tagline}</code> to keep branding references dynamic.</p>

        @foreach ($copyGroups as $group)
            <details class="branding-copy-group" @if ($loop->first) open @endif>
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
                                <label for="{{ $fieldId }}" class="form-label fw-semibold">{{ $field[1] }}</label>
                                @if ($fieldType === 'textarea')
                                    <textarea id="{{ $fieldId }}" name="{{ $fieldName }}" class="form-control" rows="2" maxlength="{{ $field[2] }}" required>{{ $fieldValue }}</textarea>
                                @else
                                    <input id="{{ $fieldId }}" name="{{ $fieldName }}" class="form-control" maxlength="{{ $field[2] }}" required value="{{ $fieldValue }}">
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </details>
        @endforeach

        <details class="branding-copy-group">
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
                                <label for="home-faq-{{ $index }}-question" data-faq-label="question" class="form-label fw-semibold">Question</label>
                                <input id="home-faq-{{ $index }}-question" name="home_content[faqs][{{ $index }}][question]" data-faq-key="question" class="form-control" maxlength="180" required value="{{ $faqQuestion }}">
                            </div>
                            <div class="mb-3">
                                <label for="home-faq-{{ $index }}-answer" data-faq-label="answer" class="form-label fw-semibold">Answer</label>
                                <textarea id="home-faq-{{ $index }}-answer" name="home_content[faqs][{{ $index }}][answer]" data-faq-key="answer" class="form-control" rows="3" maxlength="2000" required>{{ $faqAnswer }}</textarea>
                            </div>
                            <div>
                                <label for="home-faq-{{ $index }}-link_label" data-faq-label="link_label" class="form-label fw-semibold">Optional Community Dashboard link label</label>
                                <input id="home-faq-{{ $index }}-link_label" name="home_content[faqs][{{ $index }}][link_label]" data-faq-key="link_label" class="form-control" maxlength="100" value="{{ $faqLinkLabel }}">
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
            <label data-faq-label="question" class="form-label fw-semibold">Question</label>
            <input name="home_content[faqs][__INDEX__][question]" data-faq-key="question" class="form-control" maxlength="180" required>
        </div>
        <div class="mb-3">
            <label data-faq-label="answer" class="form-label fw-semibold">Answer</label>
            <textarea name="home_content[faqs][__INDEX__][answer]" data-faq-key="answer" class="form-control" rows="3" maxlength="2000" required></textarea>
        </div>
        <div>
            <label data-faq-label="link_label" class="form-label fw-semibold">Optional Community Dashboard link label</label>
            <input name="home_content[faqs][__INDEX__][link_label]" data-faq-key="link_label" class="form-control" maxlength="100">
        </div>
    </div>
</template>

@push('scripts')
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
                        field.id = `home-faq-${index}-${field.dataset.faqKey}`;
                        field.name = `home_content[faqs][${index}][${field.dataset.faqKey}]`;
                    });
                    row.querySelectorAll('[data-faq-label]').forEach((label) => {
                        label.htmlFor = `home-faq-${index}-${label.dataset.faqLabel}`;
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
                list.querySelector('[data-faq-row]:last-child [data-faq-key="question"]').focus();
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
