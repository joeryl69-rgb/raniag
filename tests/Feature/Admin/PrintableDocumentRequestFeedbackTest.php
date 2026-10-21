<?php

use App\Models\User;

test('document request results render a visible success card', function () {
    $administrator = User::factory()->administrator()->create();

    $this->actingAs($administrator)
        ->withSession([
            'document_request_success' => [
                'tone' => 'success',
                'title' => 'Request approved',
                'message' => 'The request was approved and the PDF is ready for the requesting office.',
                'tracking_number' => 'RAN-DOC123',
            ],
        ])
        ->get(route('admin.document_requests.index'))
        ->assertOk()
        ->assertSee('role="status"', false)
        ->assertSee('Request approved')
        ->assertSee('RAN-DOC123');
});
