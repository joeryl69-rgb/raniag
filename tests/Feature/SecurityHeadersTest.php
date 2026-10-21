<?php

test('content security policy allows captured media previews', function () {
    $response = $this->get(route('public.report.create'));

    $response->assertOk();
    expect($response->headers->get('Content-Security-Policy'))
        ->toContain("media-src 'self' blob: data:");
});
