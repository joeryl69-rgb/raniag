<?php

use App\Support\PlaceScope;

test('location address omits an already displayed leading barangay', function () {
    expect(PlaceScope::addressWithoutBarangayPrefix(
        'Barangay Langagan, Langagan, Sanchez Mira, Cagayan, Philippines',
        'Langagan'
    ))->toBe('Sanchez Mira, Cagayan, Philippines')
        ->and(PlaceScope::addressWithoutBarangayPrefix(
            'Main Road, Langagan, Sanchez Mira, Cagayan, Philippines',
            'Langagan'
        ))->toBe('Main Road, Langagan, Sanchez Mira, Cagayan, Philippines')
        ->and(PlaceScope::addressWithoutBarangayPrefix('Sanchez Mira, Cagayan', null))
        ->toBe('Sanchez Mira, Cagayan');
});
