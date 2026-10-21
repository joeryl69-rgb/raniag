<?php

use App\Models\Agency;
use App\Models\EvacuationCenter;
use App\Models\Evacuee;
use App\Models\Incident;
use App\Models\ReportRecord;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

function recordPayload(array $chapters, array $override = []): array
{
    return array_merge([
        'title' => 'Week of the fire',
        'date_from' => now()->startOfWeek()->toDateString(),
        'date_to' => now()->toDateString(),
        'chapters' => $chapters,
    ], $override);
}

function repeatChapter(): array
{
    return [
        'key' => (string) Str::uuid(),
        'type' => 'repeats',
        'options' => [],
    ];
}

function openRecord(): ReportRecord
{
    $admin = User::factory()->administrator()->create();
    test()->actingAs($admin);
    test()->post(route('admin.reports.store'), recordPayload(\App\Support\ReportChapterCatalog::starter()))->assertRedirect();

    return ReportRecord::query()->firstOrFail();
}

test('a new record starts with the three office chapters', function () {
    $record = openRecord();

    expect($record->status)->toBe('draft')
        ->and(collect($record->chapters)->pluck('type')->all())->toBe(['open_cases', 'shelter', 'papers']);

    $this->get(route('admin.reports.index', ['record' => $record->id]))
        ->assertOk()
        ->assertSee('Open cases', false)
        ->assertSee('Shelter roll', false)
        ->assertSee('Papers on closed cases', false)
        ->assertSee('Document details', false)
        ->assertSee('Save changes', false)
        ->assertSee('name="chapters[0][options][aor_scope]" value="aor_only"', false)
        ->assertDontSee('Which reports', false)
        ->assertDontSee('Inside and outside', false);
});

test('editing an open-cases chapter keeps its stored scope without showing the scope selector', function () {
    $record = openRecord();
    $chapters = $record->chapters;
    $chapters[0]['options']['aor_scope'] = 'all';
    $record->update(['chapters' => $chapters]);

    $this->get(route('admin.reports.index', ['record' => $record->id]))
        ->assertOk()
        ->assertSee('name="chapters[0][options][aor_scope]" value="all"', false)
        ->assertDontSee('Which reports', false)
        ->assertDontSee('Inside Pamplona', false)
        ->assertDontSee('Outside Pamplona only', false)
        ->assertDontSee('Inside and outside', false);
});

test('the report builder does not create a record until all fields validate', function () {
    $admin = User::factory()->administrator()->create();

    $this->actingAs($admin)
        ->get(route('admin.reports.index', ['view' => 'builder']))
        ->assertOk()
        ->assertSee('Build a report for a specific need', false)
        ->assertSee('Sections in this report', false)
        ->assertSee('Public communication', false)
        ->assertSee('data-chapter-template="one_case"', false)
        ->assertSee('data-chapter-template="activity"', false);

    $this->post(route('admin.reports.store'), [
        'title' => 'No',
        'date_from' => now()->startOfWeek()->toDateString(),
        'date_to' => now()->toDateString(),
        'chapters' => \App\Support\ReportChapterCatalog::starter(),
    ])->assertSessionHasErrors('title');

    expect(ReportRecord::query()->count())->toBe(0);

    $this->post(route('admin.reports.store'), [])
        ->assertSessionHasErrors(['title', 'date_from', 'date_to', 'chapters']);
    expect(ReportRecord::query()->count())->toBe(0);

    $this->post(route('admin.reports.store'), recordPayload(\App\Support\ReportChapterCatalog::starter()))
        ->assertRedirect();

    expect(ReportRecord::query()->count())->toBe(1)
        ->and(ReportRecord::query()->first()->status)->toBe('draft');
});

test('reports landing is the filtered incident presentation', function () {
    $admin = User::factory()->administrator()->create();

    $this->actingAs($admin)
        ->get(route('admin.reports.index'))
        ->assertOk()
        ->assertSee('css/workspace.css', false)
        ->assertSee('Gathered reports', false)
        ->assertSee('class="report-panel report-presentation-filters"', false)
        ->assertSee('class="rg-filter-toolbar rg-filter-toolbar-after-fields"', false)
        ->assertSee('class="rg-filter-toolbar-fields"', false)
        ->assertSee('class="rg-filter-toolbar-search"', false)
        ->assertSee('class="rg-filter-toolbar-actions"', false)
        ->assertSee('name="barangay"', false)
        ->assertSee('name="incident_type_id"', false)
        ->assertSee('name="date_from"', false)
        ->assertSee('name="date_to"', false)
        ->assertSeeInOrder([
            'name="dataset"',
            'name="barangay"',
            'name="incident_type_id"',
            'name="date_from"',
            'name="date_to"',
            'name="q"',
        ], false)
        ->assertSee('Apply filters', false)
        ->assertSee('Clear all', false)
        ->assertSee('data-auto-submit="false"', false)
        ->assertDontSee('name="aor_scope"', false)
        ->assertDontSee('Which reports', false)
        ->assertSee('Outside Pamplona', false)
        ->assertSee('Evacuee registry', false)
        ->assertDontSee('value="'.now()->startOfMonth()->toDateString().'"', false)
        ->assertDontSee('Saved reports', false)
        ->assertDontSee('Situation report', false)
        ->assertDontSee('Choose the operational records', false);

    $filterStyles = file_get_contents(public_path('css/workspace.css'));
    expect($filterStyles)
        ->toContain('.report-presentation-filters .rg-filter-toolbar-fields')
        ->toContain('.report-presentation-filters .rg-filter-toolbar-search')
        ->toContain('.report-presentation-filters .rg-filter-date');
});

test('the saved reports address returns to the gathered report page', function () {
    $admin = User::factory()->administrator()->create();

    $this->actingAs($admin)
        ->get(route('admin.reports.index', ['view' => 'archive']))
        ->assertRedirect(route('admin.reports.index'));
});

test('operations record opens with useful registers selected and optional settings folded', function () {
    $admin = User::factory()->administrator()->create();

    $this->actingAs($admin)
        ->get(route('admin.reports.create', ['template' => 'operations']))
        ->assertOk()
        ->assertSee('Daily operations and service report', false)
        ->assertSee('name="title" value="Daily operations and service report"', false)
        ->assertSee('Open cases', false)
        ->assertSee('Shelter roll', false)
        ->assertSee('Papers on closed cases', false)
        ->assertSee('Texts that failed', false)
        ->assertSee('Support still waiting', false)
        ->assertSee('Notices posted', false)
        ->assertSee('What staff changed', false)
        ->assertSee('Optional filters', false)
        ->assertSee('File report and save PDF', false);

    $this->post(route('admin.reports.store'), recordPayload(
        \App\Support\ReportChapterCatalog::operationsStarter(),
        ['title' => 'Daily operations and service report', 'action' => 'file']
    ))->assertRedirect();

    $record = ReportRecord::query()->firstOrFail();
    expect($record->status)->toBe('filed')
        ->and(count($record->chapters))->toBe(7)
        ->and(count($record->snapshot))->toBe(7)
        ->and($record->filed_path)->not->toBeNull();
});

test('quick report gathers period hotspots and files a fixed PDF snapshot', function () {
    $admin = User::factory()->administrator()->create();
    $type = \App\Models\IncidentType::factory()->create(['name' => 'Flood']);
    $trackingNumber = 1;
    foreach (['Bidduang', 'Cabaggan', 'Curva', 'Centro', 'Nagattatan', 'Santa Cruz'] as $barangay) {
        foreach ([1, 2] as $case) {
            Incident::query()->create([
                'tracking_number' => 'RAN-QCK-'.str_pad((string) $trackingNumber++, 4, '0', STR_PAD_LEFT),
                'tracking_pin' => bcrypt('secret'),
                'incident_type_id' => $type->id,
                'status' => 'submitted',
                'priority' => 'high',
                'description' => 'Flooding near the main road.',
                'barangay' => $barangay,
                'latitude' => 18.5,
                'longitude' => 121.3,
                'is_anonymous' => true,
                'reported_at' => now(),
            ]);
        }
    }

    $this->actingAs($admin)
        ->get(route('admin.reports.quick', ['period' => 'week']))
        ->assertOk()
        ->assertSee('Situation report', false)
        ->assertSee('Hotspot map by barangay', false)
        ->assertSee('Bidduang', false)
        ->assertSee('RAN-QCK-0001', false)
        ->assertSee('2 Flood reports were recorded in Bidduang', false)
        ->assertDontSee('Choose the operational records', false);

    $this->actingAs($admin)
        ->post(route('admin.reports.quick.file'), ['period' => 'week'])
        ->assertRedirect();

    $record = ReportRecord::query()->where('report_kind', 'quick')->firstOrFail();
    $quickPdfBinary = Storage::disk('local')->get($record->filed_path);
    expect($record->status)->toBe('filed')
        ->and($record->snapshot['quick_report']['metrics']['reports'])->toBe(12)
        ->and($record->snapshot['quick_report']['hotspots'])->toHaveCount(6)
        ->and(preg_match_all('/\/Type\s*\/Page\b/', $quickPdfBinary))->toBeGreaterThan(1)
        ->and($record->filed_path)->not->toBeNull();

    $quickPdfHtml = view('admin.reports.quick_pdf', [
        'record' => $record,
        'quickReport' => $record->snapshot['quick_report'],
    ])->render();
    expect($quickPdfHtml)
        ->toContain('@page { size: A4 portrait; margin: 36mm 15mm 22mm; }')
        ->toContain('.quick-hotspot-cases thead')
        ->not->toContain('rg-running-header')
        ->not->toContain('rg-running-footer')
        ->not->toContain('bottom: -20px')
        ->toContain($record->title);

    $quickPdfRenderer = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.reports.quick_pdf', [
        'record' => $record,
        'quickReport' => $record->snapshot['quick_report'],
    ])->setPaper('a4', 'portrait');
    app(\App\Services\ReportPdfPageFurniture::class)->add($quickPdfRenderer, $record->title);
    $quickPageStyle = $quickPdfRenderer->getDomPDF()->getCss()->get_page_styles()['base'];
    $quickPageCount = $quickPdfRenderer->getDomPDF()->getCanvas()->get_page_count();
    $uncompressedQuickPdf = $quickPdfRenderer->output(['compress' => false]);
    expect((int) round($quickPageStyle->margin_top))->toBe(102)
        ->and((int) round($quickPageStyle->margin_bottom))->toBe(62)
        ->and($quickPageCount)->toBeGreaterThan(1)
        ->and($uncompressedQuickPdf)->toContain('Page 1 of '.$quickPageCount)
        ->and($uncompressedQuickPdf)->toContain('Page '.$quickPageCount.' of '.$quickPageCount)
        ->and($uncompressedQuickPdf)->not->toContain('Page 1 of 0');

    $this->get(route('admin.reports.records.show', $record))
        ->assertOk()
        ->assertSee('SITUATION REPORT · FILED', false)
        ->assertSee('Bidduang', false)
        ->assertSee('RAN-QCK-0001', false);

    $this->get(route('admin.reports.download', $record))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('quick report rejects unsupported reporting periods', function () {
    $admin = User::factory()->administrator()->create();

    $this->actingAs($admin)
        ->get(route('admin.reports.quick', ['period' => 'forever']))
        ->assertSessionHasErrors('period');

    $this->post(route('admin.reports.quick.file'), ['period' => 'forever'])
        ->assertSessionHasErrors('period');

    expect(ReportRecord::query()->count())->toBe(0);
});

test('report records have explicit create show edit update and delete actions for drafts', function () {
    $admin = User::factory()->administrator()->create();
    $this->actingAs($admin)
        ->get(route('admin.reports.create'))
        ->assertOk()
        ->assertSee('Build a report for a specific need', false);

    $this->post(route('admin.reports.store'), recordPayload(\App\Support\ReportChapterCatalog::starter()))
        ->assertRedirect();
    $record = ReportRecord::query()->firstOrFail();

    $this->get(route('admin.reports.records.show', $record))
        ->assertRedirect(route('admin.reports.records.edit', $record));
    $this->get(route('admin.reports.records.edit', $record))
        ->assertOk()
        ->assertSee('Save changes', false)
        ->assertSee('action="'.route('admin.reports.records.save', $record).'"', false);

    $updated = recordPayload($record->chapters, ['title' => 'Updated weekly operations']);
    $this->post(route('admin.reports.records.save', $record), $updated)
        ->assertRedirect(route('admin.reports.records.edit', $record));
    expect($record->fresh()->title)->toBe('Updated weekly operations');

    $this->delete(route('admin.reports.records.destroy', $record))
        ->assertRedirect(route('admin.reports.index'));
    expect(ReportRecord::query()->count())->toBe(0);
});

test('invalid section settings are shown on the edit form without rendering unsafe old data', function () {
    $record = openRecord();
    $chapters = $record->chapters;
    $chapters[0]['options']['agency_id'] = ['invalid'];

    $this->from(route('admin.reports.records.edit', $record))
        ->patch(route('admin.reports.records.update', $record), recordPayload($chapters))
        ->assertSessionHasErrors('chapters.0.options.agency_id')
        ->assertRedirect(route('admin.reports.records.edit', $record));

    $this->get(route('admin.reports.records.edit', $record))
        ->assertOk()
        ->assertSee('Fix this section before saving', false)
        ->assertSee('Open cases: that office is not on file.', false);

    expect($record->fresh()->chapters[0]['options']['agency_id'])->toBeNull();
});

test('the shelter roll keeps an outside guest and leaves a pamplona guest off that chapter', function () {
    $record = openRecord();
    $center = EvacuationCenter::query()->create([
        'name' => 'Pamplona Municipal Gymnasium',
        'latitude' => 18.45,
        'longitude' => 121.34,
        'is_open' => true,
    ]);
    Evacuee::query()->create([
        'evacuation_center_id' => $center->id,
        'full_name' => 'Maria Cruz',
        'origin_scope' => 'outside',
        'origin_place' => 'Abulug',
        'barangay' => 'Centro',
        'checked_in_at' => now(),
    ]);
    Evacuee::query()->create([
        'evacuation_center_id' => $center->id,
        'full_name' => 'Ana Reyes',
        'origin_scope' => 'inside',
        'barangay' => 'Bidduang',
        'checked_in_at' => now(),
    ]);
    $chapters = $record->chapters;
    $chapters[1]['options'] = ['guest_scope' => 'outside'];

    $this->post(route('admin.reports.update', $record), recordPayload($chapters))
        ->assertRedirect();

    $this->get(route('admin.reports.index', ['record' => $record->id]))
        ->assertOk()
        ->assertSee('Maria Cruz', false)
        ->assertDontSee('Ana Reyes', false);
});

test('response sections group actual cases into the selected reporting periods', function () {
    $type = \App\Models\IncidentType::factory()->create(['name' => 'Flood']);
    foreach ([
        ['RAN-GRP-0001', now()->startOfMonth()->subDays(5)],
        ['RAN-GRP-0002', now()->startOfMonth()->addDays(2)],
    ] as [$tracking, $reportedAt]) {
        Incident::query()->create([
            'tracking_number' => $tracking,
            'tracking_pin' => bcrypt('secret'),
            'incident_type_id' => $type->id,
            'status' => 'submitted',
            'priority' => 'high',
            'description' => 'Rising water.',
            'barangay' => 'Bidduang',
            'latitude' => 18.5,
            'longitude' => 121.3,
            'is_anonymous' => true,
            'reported_at' => $reportedAt,
        ]);
    }

    $from = now()->startOfMonth()->subMonth()->toDateString();
    $to = now()->toDateString();
    $chapter = app(\App\Services\ReportChapterRenderer::class)->render([
        'key' => (string) Str::uuid(),
        'type' => 'response',
        'options' => ['view_mode' => 'monthly', 'compare' => false, 'compare_from' => null, 'compare_to' => null],
    ], $from, $to);

    expect($chapter['columns'])->toBe(['Period', 'Reports', 'Still open', 'Median to first assignment'])
        ->and($chapter['rows'])->toHaveCount(2)
        ->and($chapter['rows'][0][1])->toBe('1')
        ->and($chapter['rows'][1][1])->toBe('1');
});

test('filing locks the record and a copy opens the next draft', function () {
    $record = openRecord();
    $payload = recordPayload($record->chapters);

    $this->post(route('admin.reports.file', $record), $payload)->assertRedirect();
    $record->refresh();
    expect($record->status)->toBe('filed')
        ->and($record->filed_path)->not->toBeNull();

    $this->get(route('admin.reports.download', $record))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $this->post(route('admin.reports.update', $record), $payload)
        ->assertSessionHasErrors('title');
    $this->delete(route('admin.reports.destroy', $record))
        ->assertSessionHasErrors('title');
    $this->delete(route('admin.reports.records.destroy', $record))
        ->assertSessionHasErrors('title');
    $this->patch(route('admin.reports.records.update', $record), $payload)
        ->assertSessionHasErrors('title');

    $this->post(route('admin.reports.duplicate', $record))->assertRedirect();
    expect(ReportRecord::query()->where('status', 'draft')->count())->toBe(1);
});

test('filed documents keep the exact rows shown when the report was filed', function () {
    $record = openRecord();
    $type = \App\Models\IncidentType::factory()->create(['name' => 'Flood']);
    $incident = Incident::query()->create([
        'tracking_number' => 'RAN-SNAP-0001',
        'tracking_pin' => bcrypt('secret'),
        'incident_type_id' => $type->id,
        'status' => 'submitted',
        'priority' => 'high',
        'description' => 'Rising water.',
        'barangay' => 'Bidduang',
        'latitude' => 18.5,
        'longitude' => 121.3,
        'is_anonymous' => true,
        'reported_at' => now(),
    ]);
    $payload = recordPayload($record->chapters);

    $this->post(route('admin.reports.file', $record), $payload)->assertRedirect();

    $snapshot = $record->fresh()->snapshot;
    expect($snapshot)->not->toBeNull()
        ->and($snapshot[0]['rows'][0][0])->toBe('RAN-SNAP-0001');

    $incident->update(['barangay' => 'Cabaroan']);

    $this->get(route('admin.reports.index', ['record' => $record->id]))
        ->assertOk()
        ->assertSee('Bidduang', false)
        ->assertDontSee('Cabaroan', false);
});

test('a chapter spreadsheet uses the stored chapter', function () {
    $record = openRecord();
    $key = $record->chapters[0]['key'];

    $this->post(route('admin.reports.export', [$record, $key]))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $this->post(route('admin.reports.export', [$record, (string) Str::uuid()]))
        ->assertNotFound();

    $this->post(route('admin.reports.export', [$record, $record->chapters[2]['key']]))
        ->assertSessionHasErrors('chapter');
});

test('a record rejects invalid input', function (array $override, string $field) {
    $record = openRecord();
    $payload = recordPayload([repeatChapter()], $override);

    $this->from(route('admin.reports.index', ['record' => $record->id]))
        ->post(route('admin.reports.update', $record), $payload)
        ->assertSessionHasErrors($field);

    expect($record->fresh()->title)->toBe($record->title);
})->with([
    'blank title' => [['title' => '   '], 'title'],
    'short title' => [['title' => 'No'], 'title'],
    'long title' => [['title' => str_repeat('A', 121)], 'title'],
    'punctuation title' => [['title' => '---'], 'title'],
    'end before start' => [['date_from' => now()->toDateString(), 'date_to' => now()->subDay()->toDateString()], 'date_to'],
    'future end' => [['date_to' => now()->addDay()->toDateString()], 'date_to'],
    'span over a year' => [['date_from' => now()->subDays(400)->toDateString(), 'date_to' => now()->toDateString()], 'date_to'],
    'no chapters' => [['chapters' => []], 'chapters'],
    'thirteen chapters' => [['chapters' => array_map(fn () => repeatChapter(), range(1, 13))], 'chapters'],
    'unknown chapter' => [['chapters' => [['key' => (string) Str::uuid(), 'type' => 'poster', 'options' => []]]], 'chapters.0.type'],
    'duplicate identity' => [[
        'chapters' => (function () {
            $chapter = repeatChapter();

            return [$chapter, $chapter];
        })(),
    ], 'chapters.1.key'],
    'unknown option' => [[
        'chapters' => [['key' => (string) Str::uuid(), 'type' => 'repeats', 'options' => ['barangay' => 'Centro']]],
    ], 'chapters.0.options.barangay'],
    'non-array options are rejected' => [[
        'chapters' => [['key' => (string) Str::uuid(), 'type' => 'repeats', 'options' => 'not-an-array']],
    ], 'chapters.0.options'],
    'missing options are rejected' => [[
        'chapters' => [['key' => (string) Str::uuid(), 'type' => 'repeats']],
    ], 'chapters.0.options'],
    'extra chapter attributes are rejected' => [[
        'chapters' => [['key' => (string) Str::uuid(), 'type' => 'repeats', 'options' => [], 'unexpected' => true]],
    ], 'chapters.0'],
    'natural language dates are rejected' => [['date_from' => 'yesterday'], 'date_from'],
    'bad barangay' => [[
        'chapters' => [[
            'key' => (string) Str::uuid(),
            'type' => 'open_cases',
            'options' => ['aor_scope' => 'aor_only', 'barangay' => 'Not a place', 'agency_id' => null, 'incident_type_id' => null],
        ]],
    ], 'chapters.0.options.barangay'],
    'unknown office' => [[
        'chapters' => [[
            'key' => (string) Str::uuid(),
            'type' => 'open_cases',
            'options' => ['aor_scope' => 'aor_only', 'barangay' => null, 'agency_id' => 99999, 'incident_type_id' => null],
        ]],
    ], 'chapters.0.options.agency_id'],
    'array office id' => [[
        'chapters' => [[
            'key' => (string) Str::uuid(),
            'type' => 'open_cases',
            'options' => ['aor_scope' => 'aor_only', 'barangay' => null, 'agency_id' => [1], 'incident_type_id' => null],
        ]],
    ], 'chapters.0.options.agency_id'],
    'comparison not earlier' => [[
        'chapters' => [[
            'key' => (string) Str::uuid(),
            'type' => 'response',
            'options' => [
                'view_mode' => 'weekly',
                'compare' => '1',
                'compare_from' => now()->subDays(3)->toDateString(),
                'compare_to' => now()->toDateString(),
            ],
        ]],
    ], 'chapters.0.options.compare_to'],
    'shelter scope missing' => [[
        'chapters' => [['key' => (string) Str::uuid(), 'type' => 'shelter', 'options' => []]],
    ], 'chapters.0.options.guest_scope'],
    'paper completion' => [[
        'chapters' => [['key' => (string) Str::uuid(), 'type' => 'papers', 'options' => ['document_type' => 'all', 'completion' => 'sometimes']]],
    ], 'chapters.0.options.completion'],
    'text status' => [[
        'chapters' => [['key' => (string) Str::uuid(), 'type' => 'texts', 'options' => ['status' => 'queued']]],
    ], 'chapters.0.options.status'],
    'support topic' => [[
        'chapters' => [['key' => (string) Str::uuid(), 'type' => 'support', 'options' => ['status' => 'new', 'category' => 'weather', 'source' => 'all']]],
    ], 'chapters.0.options.category'],
    'unknown log' => [[
        'chapters' => [['key' => (string) Str::uuid(), 'type' => 'activity', 'options' => ['log_name' => 'not-a-log']]],
    ], 'chapters.0.options.log_name'],
    'unknown case' => [[
        'chapters' => [['key' => (string) Str::uuid(), 'type' => 'one_case', 'options' => ['tracking_number' => 'RAN-NOPE']]],
    ], 'chapters.0.options.tracking_number'],
    'array tracking number' => [[
        'chapters' => [['key' => (string) Str::uuid(), 'type' => 'one_case', 'options' => ['tracking_number' => ['RAN-NOPE']]]],
    ], 'chapters.0.options.tracking_number'],
    'same chapter twice' => [[
        'chapters' => [repeatChapter(), repeatChapter()],
    ], 'chapters.1.type'],
]);

test('an open-cases chapter accepts a real office', function () {
    $record = openRecord();
    $agency = Agency::query()->create([
        'name' => 'Bureau of Fire Protection',
        'code' => 'BFP',
    ]);
    Incident::query()->create([
        'tracking_number' => 'RAN-RPT-0099',
        'tracking_pin' => bcrypt('secret'),
        'incident_type_id' => \App\Models\IncidentType::factory()->create()->id,
        'status' => 'submitted',
        'priority' => 'high',
        'description' => 'Grass fire.',
        'barangay' => 'Bidduang',
        'latitude' => 18.5,
        'longitude' => 121.3,
        'is_anonymous' => true,
        'reported_at' => now(),
        'agency_id' => $agency->id,
    ]);

    $this->post(route('admin.reports.update', $record), recordPayload([[
        'key' => (string) Str::uuid(),
        'type' => 'open_cases',
        'options' => [
            'aor_scope' => 'aor_only',
            'barangay' => 'Bidduang',
            'agency_id' => $agency->id,
            'incident_type_id' => null,
        ],
    ]]))->assertRedirect();

    $this->get(route('admin.reports.index', ['record' => $record->id]))
        ->assertOk()
        ->assertSee('RAN-RPT-0099', false);
});
