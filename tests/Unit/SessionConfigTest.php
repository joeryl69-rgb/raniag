<?php

uses(Tests\TestCase::class);

it('uses browser-safe session cookie settings in local development', function () {
    expect(config('session.same_site'))->toBe('lax');
    expect(config('session.secure'))->toBeFalse();
});
