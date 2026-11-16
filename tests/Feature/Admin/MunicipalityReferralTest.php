<?php

use App\Enums\IncidentStatus;
use App\Models\Incident;
use App\Models\IncidentReferral;
use App\Models\IncidentType;
use App\Models\MunicipalityContact;
use App\Models\StatusUpdate;
use App\Models\User;

function referralTestIncident(string $trackingNumber): Incident
{
    return Incident::query()->create([
        'tracking_number' => $trackingNumber,
        'tracking_pin' => bcrypt('123456'),
        'incident_type_id' => IncidentType::factory()->create()->id,
        'status' => IncidentStatus::Submitted,
        'priority' => 'medium',
        'description' => 'Referral test report.',
        'is_anonymous' => true,
        'reported_at' => now(),
        'latitude' => 18.45,
        'longitude' => 121.32,
    ]);
}

test('admins can manage multiple municipal office contacts and select their location pin', function () {
    $admin = User::factory()->administrator()->create();

    $this->actingAs($admin)
        ->get(route('admin.referral_contacts.index'))
        ->assertOk()
        ->assertSee('Add referral contact')
        ->assertSee('id="municipalityContactModal"', false)
        ->assertSee('Office map pin', false)
        ->assertDontSee('Map latitude', false)
        ->assertDontSee('Map longitude', false);

    $this->post(route('admin.referral_contacts.store'), [
        'municipality' => 'Sanchez Mira',
        'office_name' => 'Municipal Disaster Risk Reduction Office',
        'phone' => '+63 917 000 1111',
        'email' => 'mdrrmo@example.test',
        'hotline' => '0917-000-2222',
        'address' => 'Municipal Hall, Sanchez Mira',
        'latitude' => 18.57,
        'longitude' => 121.23,
        'is_active' => '1',
    ])->assertRedirect();

    $contact = MunicipalityContact::query()->sole();
    expect($contact->municipality)->toBe('Sanchez Mira')
        ->and($contact->phone)->toBe('+63 917 000 1111')
        ->and($contact->email)->toBe('mdrrmo@example.test')
        ->and($contact->hotline)->toBe('0917-000-2222')
        ->and((float) $contact->latitude)->toBe(18.57)
        ->and($contact->is_active)->toBeTrue();

    $this->get(route('admin.referral_contacts.index'))
        ->assertOk()
        ->assertSee('js-edit-municipality-contact', false)
        ->assertSee('data-contact-id="'.$contact->id.'"', false);

    $this->post(route('admin.referral_contacts.store'), [
        'municipality' => 'Sanchez Mira',
        'office_name' => 'Sanchez Mira Police Station',
        'email' => 'police@example.test',
    ])->assertRedirect();
    expect(MunicipalityContact::query()->where('municipality', 'Sanchez Mira')->count())->toBe(2);

    $this->put(route('admin.referral_contacts.update', $contact), [
        'municipality' => 'Sanchez Mira',
        'office_name' => 'Updated MDRRMO',
        'phone' => '+63 917 000 1111',
        'email' => 'updated@example.test',
        'hotline' => '0917-000-3333',
        'latitude' => 18.58,
        'longitude' => 121.24,
    ])->assertRedirect();

    $contact->refresh();
    expect($contact->office_name)->toBe('Updated MDRRMO')
        ->and($contact->is_active)->toBeFalse()
        ->and($contact->hotline)->toBe('0917-000-3333');
});

test('referring an outside-AOR report stores a public-safe destination snapshot and live tracking details', function () {
    $admin = User::factory()->administrator()->create();
    $incident = referralTestIncident('RAN-REFERRAL-LIVE');
    $contact = MunicipalityContact::query()->create([
        'municipality' => 'Sanchez Mira',
        'office_name' => 'Municipal MDRRMO',
        'phone' => '09170001111',
        'email' => 'private-office@example.test',
        'hotline' => '09170002222',
        'address' => 'Municipal Hall, Sanchez Mira',
        'latitude' => 18.57,
        'longitude' => 121.23,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.incidents.show', $incident))
        ->assertOk()
        ->assertSee('Sanchez Mira · Municipal MDRRMO')
        ->assertSee('referral-hotline-call', false);

    $session = ['track_verified.'.$incident->id => true];
    $this->withSession($session)
        ->get(route('public.track.case', $incident->tracking_number))
        ->assertOk()
        ->assertDontSee('id="rg-track-referral"', false);

    $this->actingAs($admin)
        ->post(route('admin.incidents.validate', $incident), [
            'action' => 'outside_aor',
            'municipality_contact_id' => $contact->id,
            'notes' => 'Coordinated response with the receiving municipality.',
        ])
        ->assertRedirect(route('admin.incidents.show', $incident->id));

    expect($incident->fresh()->status)->toBe(IncidentStatus::OutsideAor);
    $referral = IncidentReferral::query()->sole();
    expect($referral->municipality)->toBe('Sanchez Mira')
        ->and($referral->office_name)->toBe('Municipal MDRRMO')
        ->and($referral->hotline)->toBe('09170002222')
        ->and((float) $referral->latitude)->toBe(18.57)
        ->and($referral->public_note)->toBe('Coordinated response with the receiving municipality.');

    $this->withSession($session)
        ->get(route('public.track.case', $incident->tracking_number))
        ->assertOk()
        ->assertSee('id="track-live-map"', false)
        ->assertSee('referralPoint:', false)
        ->assertSee('Municipal MDRRMO');

    $updates = $this->withSession($session)
        ->get(route('public.track.updates', $incident->tracking_number))
        ->assertOk()
        ->assertSee('CASE MOVEMENT / OUTSIDE AOR')
        ->assertSee('Coordination destination')
        ->assertSee('Sanchez Mira')
        ->assertSee('Municipal MDRRMO')
        ->assertSee('Municipal Hall, Sanchez Mira')
        ->assertSee('Coordinated response with the receiving municipality.')
        ->assertSee('href="tel:09170002222"', false)
        ->assertSee('data-latitude="18.57000000"', false)
        ->assertSee('data-longitude="121.23000000"', false);
    expect($updates->getContent())
        ->not->toContain('private-office@example.test')
        ->not->toContain('09170001111');

    $contact->update([
        'municipality' => 'Changed Municipality',
        'hotline' => '09999999999',
    ]);
    $contact->delete();

    $referral->refresh();
    expect($referral->municipality)->toBe('Sanchez Mira')
        ->and($referral->hotline)->toBe('09170002222')
        ->and($referral->municipality_contact_id)->toBeNull();
});

test('outside-AOR referrals require a selected active contact and public note', function () {
    $admin = User::factory()->administrator()->create();
    $incident = referralTestIncident('RAN-REFERRAL-VALIDATION');
    $inactive = MunicipalityContact::query()->create([
        'municipality' => 'Inactive Municipality',
        'office_name' => 'Inactive Office',
        'hotline' => '09170003333',
        'is_active' => false,
    ]);

    $this->actingAs($admin)
        ->from(route('admin.incidents.show', $incident))
        ->post(route('admin.incidents.validate', $incident), [
            'action' => 'outside_aor',
            'municipality_contact_id' => $inactive->id,
            'notes' => 'Referral note',
        ])
        ->assertRedirect(route('admin.incidents.show', $incident))
        ->assertSessionHasErrors('municipality_contact_id');

    $this->post(route('admin.incidents.validate', $incident), [
        'action' => 'outside_aor',
        'municipality_contact_id' => '',
        'notes' => '',
    ])->assertSessionHasErrors(['municipality_contact_id', 'notes']);

    expect($incident->fresh()->status)->toBe(IncidentStatus::Submitted)
        ->and(IncidentReferral::query()->count())->toBe(0)
        ->and(StatusUpdate::query()->where('incident_id', $incident->id)->count())->toBe(0);
});

test('live tracking adds the receiving-office pin when the report has no incident GPS point', function () {
    $admin = User::factory()->administrator()->create();
    $incident = referralTestIncident('RAN-REFERRAL-NO-GPS');
    $incident->update(['latitude' => null, 'longitude' => null]);
    $contact = MunicipalityContact::query()->create([
        'municipality' => 'Claveria',
        'office_name' => 'Claveria MDRRMO',
        'hotline' => '09170004444',
        'latitude' => 18.61,
        'longitude' => 121.08,
        'is_active' => true,
    ]);
    $session = ['track_verified.'.$incident->id => true];

    $this->withSession($session)
        ->get(route('public.track.case', $incident->tracking_number))
        ->assertOk()
        ->assertDontSee('id="track-live-map"', false)
        ->assertDontSee('id="rg-track-referral-map"', false)
        ->assertSee('js/public-track-map.js', false);

    $this->actingAs($admin)
        ->post(route('admin.incidents.validate', $incident), [
            'action' => 'outside_aor',
            'municipality_contact_id' => $contact->id,
            'notes' => 'The receiving office is coordinating this report.',
        ])
        ->assertRedirect();

    $this->withSession($session)
        ->get(route('public.track.updates', $incident->tracking_number))
        ->assertOk()
        ->assertSee('id="rg-track-referral-map"', false)
        ->assertSee('data-latitude="18.61000000"', false)
        ->assertSee('data-longitude="121.08000000"', false)
        ->assertSee('Claveria MDRRMO');
});

test('municipality contacts require at least one usable contact channel and paired coordinates', function () {
    $admin = User::factory()->administrator()->create();

    $this->actingAs($admin)
        ->from(route('admin.referral_contacts.index'))
        ->post(route('admin.referral_contacts.store'), [
            'municipality' => 'Contactless Town',
            'office_name' => 'Contactless Office',
            'phone' => '',
            'email' => '',
            'hotline' => '',
            'latitude' => 18.5,
        ])
        ->assertRedirect(route('admin.referral_contacts.index'))
        ->assertSessionHasErrors(['phone', 'email', 'hotline', 'longitude']);

    expect(MunicipalityContact::query()->count())->toBe(0);
});
