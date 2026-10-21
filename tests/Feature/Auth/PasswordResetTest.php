<?php

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Enums\UserRole;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Notification;

test('reset password link screen can be rendered', function () {
    $response = $this->get('/forgot-password');

    $response->assertStatus(200);
});

test('reset password link can be requested', function () {
    Notification::fake();

    $user = User::factory()->create(['role' => UserRole::Agency]);

    $this->post('/forgot-password', ['email' => $user->email])
        ->assertSessionHas('status');

    Notification::assertSentTo($user, ResetPasswordNotification::class);
});

test('reset link email sends without requiring a queue worker', function () {
    expect(is_subclass_of(ResetPasswordNotification::class, ShouldQueue::class))->toBeFalse();
});

test('repeated reset-link requests show a safe status without sending duplicate mail', function () {
    Notification::fake();
    $user = User::factory()->create(['role' => UserRole::Personnel]);

    foreach ([1, 2] as $_) {
        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHas('status', 'If an account matches that email, we will send a reset link. If you requested one recently, check your inbox before trying again.')
            ->assertSessionHasNoErrors();
    }

    Notification::assertSentToTimes($user, ResetPasswordNotification::class, 1);
});

test('custom reset email omits the raw-link fallback and is sent only once', function () {
    $user = User::factory()->create(['role' => UserRole::Agency]);
    $notification = new ResetPasswordNotification('test-token');
    $mail = $notification->toMail($user);

    expect($mail)->toBeInstanceOf(Mailable::class);
    expect($mail->render())->not->toContain('If you\'re having trouble clicking');
    expect(view('emails.auth.reset-password-text')->render())->not->toContain('test-token');
});

test('reset password screen can be rendered', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) {
        $response = $this->get('/reset-password/'.$notification->token);

        $response->assertStatus(200);

        return true;
    });
});

test('password can be reset with valid token', function () {
    Notification::fake();

    $user = User::factory()->create(['role' => UserRole::Agency]);

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use ($user) {
        $response = $this->post('/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'Reset-password1!',
            'password_confirmation' => 'Reset-password1!',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        return true;
    });
});

test('reset password rejects a password that does not meet every displayed requirement', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use ($user) {
        $this->post('/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'alllowercase',
            'password_confirmation' => 'alllowercase',
        ])->assertSessionHasErrors('password');

        return true;
    });
});
