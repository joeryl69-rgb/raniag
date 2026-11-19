<?php

use App\Support\PlaceScope;

test('location address omits an already displayed leading barangay', function () {
    expect(PlaceScope::addressWithoutBarangayPrefix(
        'Barangay Langagan, Langagan, Sanchez Mira, Cagayan, Philippines',
        'Langagan',
        'Cagayan'
    ))->toBe('Sanchez Mira, Cagayan, Philippines')
        ->and(PlaceScope::addressWithoutBarangayPrefix(
            'Main Road, Langagan, Sanchez Mira, Cagayan, Philippines',
            'Langagan',
            'Cagayan'
        ))->toBe('Main Road, Langagan, Sanchez Mira, Cagayan, Philippines')
        ->and(PlaceScope::addressWithoutBarangayPrefix('Sanchez Mira, Cagayan', null, 'Cagayan'))
        ->toBe('Sanchez Mira, Cagayan');
});

test('location address omits the configured province when the region already includes it', function () {
    expect(PlaceScope::addressWithoutBarangayPrefix(
        'Langagan, Sanchez Mira, Cagayan, Cagayan Valley, Philippines',
        'Langagan',
        'Cagayan'
    ))->toBe('Sanchez Mira, Cagayan Valley, Philippines');
});

test('location address uses the configured province label instead of a fixed province name', function () {
    expect(PlaceScope::addressWithoutBarangayPrefix(
        'Langagan, Sanchez Mira, Sierra Province, Sierra Province Region, Philippines',
        'Langagan',
        'Sierra Province'
    ))->toBe('Sanchez Mira, Sierra Province Region, Philippines');
});
