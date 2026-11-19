<?php

use App\Models\SystemSetting;
use App\Models\User;

test('administrator can edit the public page copy and dynamic FAQ list without changing route behavior', function () {
    $admin = User::factory()->administrator()->create();
    $content = config('public_home');
    $content['shell']['home_title'] = 'Safety desk';
    $content['shell']['navigation']['home'] = 'Welcome';
    $content['hero']['title_lead'] = 'Ready when it matters,';
    $content['hero']['title_emphasis'] = 'here for every neighbor.';
    $content['faqs'] = [
        [
            'question' => 'How fast will {organization} respond?',
            'answer' => 'Help for {municipality}. <script>alert(1)</script>',
            'link_label' => 'See local trends',
        ],
    ];

    $this->actingAs($admin)
        ->get(route('admin.branding.index'))
        ->assertOk()
        ->assertSee('Public site content')
        ->assertSee('Enter the words you want visitors to see.')
        ->assertSee('Insert a live detail')
        ->assertSee('data-copy-token-select', false)
        ->assertSee('data-current-value="Cagayan Valley"', false)
        ->assertSee('data-source="Branding &amp; System Settings &gt; Location display labels &gt; Response area / region"', false)
        ->assertSee('data-copy-home-map', false)
        ->assertSee('data-copy-map-link="faqs"', false)
        ->assertSee('data-copy-group="faqs"', false)
        ->assertSee('home_content[faqs][0][question]', false)
        ->assertSee('Add a question');

    $this->put(route('admin.branding.update'), [
        'system_name' => 'Safety Desk',
        'organization_name' => 'Response Office',
        'primary_color' => '#086b78',
        'accent_color' => '#29aa96',
        'page_background_color' => '#eaf0f2',
        'municipality_label' => 'Harborview',
        'province_label' => 'North Province',
        'response_area_label' => 'Northern Region',
        'home_content' => $content,
    ])->assertRedirect(route('admin.branding.index'))
        ->assertSessionHasNoErrors();

    expect(count(SystemSetting::current()->public_home_content['faqs']))->toBe(1);

    $this->get(route('admin.branding.index'))
        ->assertOk()
        ->assertSee('data-copy-token="organization"', false)
        ->assertSee('Organization name')
        ->assertSee('value="How fast will Response Office respond?"', false)
        ->assertDontSee('Text is escaped for safety')
        ->assertDontSee('Use <code>{organization}</code>', false);

    $this->get(route('public.home'))
        ->assertOk()
        ->assertSee('<title>Safety desk — Response Office</title>', false)
        ->assertSee('Welcome')
        ->assertSee('Ready when it matters,')
        ->assertSee('How fast will Response Office respond?')
        ->assertSee('Help for Harborview.')
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('See local trends')
        ->assertSee('href="'.route('public.dashboard').'"', false)
        ->assertSee('Incident Reporting and Analytics System for Response Office');
});

test('invalid FAQ counts and missing answers are rejected without saving content', function () {
    $admin = User::factory()->administrator()->create();
    $content = config('public_home');
    $content['faqs'] = array_fill(0, 13, [
        'question' => 'Question',
        'answer' => 'Answer',
        'link_label' => '',
    ]);

    $this->actingAs($admin)
        ->from(route('admin.branding.index'))
        ->put(route('admin.branding.update'), [
            'system_name' => 'RANIAG',
            'organization_name' => 'MDRRMO Pamplona',
            'primary_color' => '#086b78',
            'accent_color' => '#29aa96',
            'page_background_color' => '#eaf0f2',
            'municipality_label' => 'Pamplona',
            'province_label' => 'Cagayan',
            'response_area_label' => 'Cagayan Valley',
            'home_content' => $content,
        ])
        ->assertSessionHasErrors(['home_content.faqs']);

    expect(SystemSetting::query()->exists())->toBeFalse();

    $content['faqs'] = [['question' => 'Question', 'answer' => '', 'link_label' => '']];

    $this->actingAs($admin)
        ->from(route('admin.branding.index'))
        ->put(route('admin.branding.update'), [
            'system_name' => 'RANIAG',
            'organization_name' => 'MDRRMO Pamplona',
            'primary_color' => '#086b78',
            'accent_color' => '#29aa96',
            'page_background_color' => '#eaf0f2',
            'municipality_label' => 'Pamplona',
            'province_label' => 'Cagayan',
            'response_area_label' => 'Cagayan Valley',
            'home_content' => $content,
        ])
        ->assertSessionHasErrors(['home_content.faqs.0.answer']);

    expect(SystemSetting::query()->exists())->toBeFalse();
});
