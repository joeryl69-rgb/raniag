<?php

use App\Enums\IncidentStatus;
use App\Mail\IncidentReportReceivedMail;
use App\Mail\IncidentStatusUpdateMail;
use App\Models\Incident;
use App\Models\IncidentType;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

test('reporter receipt notification sends email and sms channels when contact present', function () {
    Mail::fake();

    $type = IncidentType::factory()->create();
    $incident = Incident::query()->create([
        'tracking_number' => 'RAN-RCV-0001',
        'tracking_pin' => Hash::make('111111'),
        'incident_type_id' => $type->id,
        'status' => IncidentStatus::Submitted,
        'priority' => 'medium',
        'description' => 'Reporter notify test.',
        'is_anonymous' => false,
        'reporter_email' => 'citizen@example.com',
        'reporter_phone' => '09171234567',
        'reported_at' => now(),
    ]);

    app(NotificationService::class)->notifyReporterReportReceived($incident);

    Mail::assertSent(IncidentReportReceivedMail::class, function (IncidentReportReceivedMail $mail) use ($incident) {
        return $mail->incident->id === $incident->id
            && str_contains($mail->trackUrl, 'tracking_number='.$incident->tracking_number);
    });
});

test('anonymous reporters do not receive receipt notifications', function () {
    Mail::fake();

    $type = IncidentType::factory()->create();
    $incident = Incident::query()->create([
        'tracking_number' => 'RAN-RCV-0002',
        'tracking_pin' => Hash::make('111111'),
        'incident_type_id' => $type->id,
        'status' => IncidentStatus::Submitted,
        'priority' => 'medium',
        'description' => 'Anonymous notify skip test.',
        'is_anonymous' => true,
        'reported_at' => now(),
    ]);

    app(NotificationService::class)->notifyReporterReportReceived($incident);

    Mail::assertNothingSent();
});

test('status update mail includes tracking deep link', function () {
    $type = IncidentType::factory()->create();
    $incident = Incident::query()->create([
        'tracking_number' => 'RAN-RCV-0003',
        'tracking_pin' => Hash::make('111111'),
        'incident_type_id' => $type->id,
        'status' => IncidentStatus::Assigned,
        'priority' => 'medium',
        'description' => 'Status mail link test.',
        'is_anonymous' => false,
        'reporter_email' => 'citizen@example.com',
        'reported_at' => now(),
    ]);

    $mailable = new IncidentStatusUpdateMail($incident, 'Agency assigned.', route('public.track', [
        'tracking_number' => $incident->tracking_number,
    ]));

    $mailable->assertSeeInHtml('View live status');
    $mailable->assertSeeInHtml('tracking_number='.$incident->tracking_number);
});
