<?php

use App\Enums\UserRole;
use App\Models\Agency;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('personnel password reset requires a strong matching password', function () {
    $admin = User::factory()->administrator()->create();
    $personnel = User::factory()->create([
        'role' => UserRole::Personnel,
        'role_title' => 'Responder',
        'team_assignment' => 'Field Team',
    ]);
    $payload = [
        'name' => $personnel->name,
        'email' => $personnel->email,
        'phone' => $personnel->phone,
        'role_title' => $personnel->role_title,
        'team_assignment' => $personnel->team_assignment,
        'password' => 'weakpass1',
        'password_confirmation' => 'weakpass1',
    ];

    $this->actingAs($admin)
        ->get(route('admin.personnel.edit', $personnel))
        ->assertOk()
        ->assertSee('data-password-strength', false)
        ->assertSee('data-password-optional', false)
        ->assertSee('data-password-toggle', false)
        ->assertSee('password-strength.js', false);

    $originalPassword = $personnel->password;
    unset($payload['password'], $payload['password_confirmation']);
    $this->actingAs($admin)
        ->put(route('admin.personnel.update', $personnel), $payload)
        ->assertRedirect(route('admin.agencies.index'));
    expect($personnel->fresh()->password)->toBe($originalPassword);

    $payload['password'] = 'weakpass1';
    $payload['password_confirmation'] = 'weakpass1';
    $this->actingAs($admin)
        ->put(route('admin.personnel.update', $personnel), $payload)
        ->assertSessionHasErrors('password');

    $payload['password'] = 'StrongPass1!';
    $payload['password_confirmation'] = 'DifferentPass1!';
    $this->actingAs($admin)
        ->put(route('admin.personnel.update', $personnel), $payload)
        ->assertSessionHasErrors('password_confirmation');

    $payload['password_confirmation'] = $payload['password'];
    $this->actingAs($admin)
        ->put(route('admin.personnel.update', $personnel), $payload)
        ->assertRedirect(route('admin.agencies.index'));

    expect(Hash::check('StrongPass1!', $personnel->fresh()->password))->toBeTrue();
});

test('agency officer password reset requires a strong matching password', function () {
    $admin = User::factory()->administrator()->create();
    $agency = Agency::query()->create([
        'name' => 'Test Agency',
        'code' => 'TEST-AGENCY',
        'is_active' => true,
        'is_available' => true,
    ]);
    $officer = User::factory()->agency()->create(['agency_id' => $agency->id]);
    $payload = [
        'name' => $agency->name,
        'code' => $agency->code,
        'description' => null,
        'phone' => null,
        'address' => null,
        'officer_name' => $officer->name,
        'officer_email' => $officer->email,
        'officer_password' => 'weakpass1',
        'officer_password_confirmation' => 'weakpass1',
    ];

    $this->actingAs($admin)
        ->get(route('admin.agencies.edit', $agency))
        ->assertOk()
        ->assertSee('data-password-strength', false)
        ->assertSee('data-password-optional', false)
        ->assertSee('data-password-toggle', false)
        ->assertSee('password-strength.js', false);

    $originalPassword = $officer->password;
    unset($payload['officer_password'], $payload['officer_password_confirmation']);
    $this->actingAs($admin)
        ->put(route('admin.agencies.update', $agency), $payload)
        ->assertRedirect(route('admin.agencies.index'));
    expect($officer->fresh()->password)->toBe($originalPassword);

    $payload['officer_password'] = 'weakpass1';
    $payload['officer_password_confirmation'] = 'weakpass1';
    $this->actingAs($admin)
        ->put(route('admin.agencies.update', $agency), $payload)
        ->assertSessionHasErrors('officer_password');

    $payload['officer_password'] = 'StrongPass1!';
    $payload['officer_password_confirmation'] = 'StrongPass1!';
    $this->actingAs($admin)
        ->put(route('admin.agencies.update', $agency), $payload)
        ->assertRedirect(route('admin.agencies.index'));

    expect(Hash::check('StrongPass1!', $officer->fresh()->password))->toBeTrue();
});
