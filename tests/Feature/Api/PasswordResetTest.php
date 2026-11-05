<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

it('sends a reset link that points to the frontend', function () {
    Notification::fake();
    config(['app.frontend_url' => 'https://bantaypresyo.example']);
    $user = User::factory()->create(['email' => 'maria@example.com']);

    $this->postJson('/api/v1/auth/forgot-password', ['email' => 'maria@example.com'])->assertOk();

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
        $url = $notification->toMail($user)->actionUrl;

        return str_starts_with($url, 'https://bantaypresyo.example/reset-password?token=')
            && str_contains($url, 'email=maria%40example.com');
    });
});

it('gives the same answer for unknown emails', function () {
    Notification::fake();

    $this->postJson('/api/v1/auth/forgot-password', ['email' => 'nobody@example.com'])
        ->assertOk()
        ->assertJsonPath('data.message', 'If that email is registered, a password reset link has been sent.');

    Notification::assertNothingSent();
});

it('resets the password and revokes existing tokens', function () {
    $user = User::factory()->create(['email' => 'maria@example.com']);
    $user->createToken('phone');
    $token = Password::createToken($user);

    $this->postJson('/api/v1/auth/reset-password', [
        'token' => $token,
        'email' => 'maria@example.com',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertOk();

    $user->refresh();
    expect(Hash::check('new-password', $user->password))->toBeTrue()
        ->and($user->tokens()->count())->toBe(0);
});

it('rejects an invalid reset token', function () {
    User::factory()->create(['email' => 'maria@example.com']);

    $this->postJson('/api/v1/auth/reset-password', [
        'token' => 'not-a-real-token',
        'email' => 'maria@example.com',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])
        ->assertUnprocessable()
        ->assertJsonStructure(['error' => ['details' => ['email']]]);
});
