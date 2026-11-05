<?php

use App\Models\Commodity;
use App\Models\Market;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;

function verificationUrl(User $user): string
{
    return URL::temporarySignedRoute('verification.verify', now()->addHour(), [
        'id' => $user->id,
        'hash' => sha1($user->email),
    ]);
}

it('sends a verification email on registration', function () {
    Notification::fake();

    $this->postJson('/api/v1/auth/register', [
        'name' => 'Maria Santos',
        'email' => 'maria@example.com',
        'password' => 'secret-pass',
        'password_confirmation' => 'secret-pass',
    ])
        ->assertCreated()
        ->assertJsonPath('data.user.email_verified', false);

    Notification::assertSentTo(User::query()->where('email', 'maria@example.com')->sole(), VerifyEmail::class);
});

it('blocks price reports from unverified users', function () {
    Sanctum::actingAs(User::factory()->unverified()->create());

    $this->postJson('/api/v1/price-reports', [
        'commodity_id' => Commodity::factory()->create()->id,
        'market_id' => Market::factory()->create()->id,
        'price' => 50,
    ])
        ->assertForbidden()
        ->assertJsonPath('error.code', 'email_not_verified');
});

it('verifies the email from a signed link', function () {
    $user = User::factory()->unverified()->create();

    $this->getJson(verificationUrl($user))->assertOk();

    expect($user->refresh()->hasVerifiedEmail())->toBeTrue();
});

it('rejects a tampered verification link', function () {
    $user = User::factory()->unverified()->create();

    $this->getJson(verificationUrl($user).'x')->assertForbidden();

    $wrongHash = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
        'id' => $user->id,
        'hash' => sha1('someone-else@example.com'),
    ]);
    $this->getJson($wrongHash)->assertForbidden();

    expect($user->refresh()->hasVerifiedEmail())->toBeFalse();
});

it('resends the verification email', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/auth/email/verification-notification')->assertOk();

    Notification::assertSentTo($user, VerifyEmail::class);
});
