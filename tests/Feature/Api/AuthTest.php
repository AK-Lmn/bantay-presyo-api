<?php

use App\Models\User;

it('registers a user and returns a token', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Maria Santos',
        'email' => 'maria@example.com',
        'password' => 'secret-pass',
        'password_confirmation' => 'secret-pass',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.user.email', 'maria@example.com')
        ->assertJsonPath('data.user.is_admin', false)
        ->assertJsonPath('data.token_type', 'Bearer');

    expect($response->json('data.token'))->toBeString()->not->toBeEmpty();
});

it('rejects registration with a taken email or short password', function () {
    User::factory()->create(['email' => 'maria@example.com']);

    $this->postJson('/api/v1/auth/register', [
        'name' => 'Maria Santos',
        'email' => 'maria@example.com',
        'password' => 'short',
        'password_confirmation' => 'short',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('error.code', 'validation_failed')
        ->assertJsonStructure(['error' => ['code', 'message', 'details' => ['email', 'password']]]);
});

it('logs in with valid credentials only', function () {
    User::factory()->create(['email' => 'juan@example.com', 'password' => 'correct-horse']);

    $this->postJson('/api/v1/auth/login', ['email' => 'juan@example.com', 'password' => 'wrong'])
        ->assertUnprocessable()
        ->assertJsonPath('error.details.email.0', 'These credentials do not match our records.');

    $this->postJson('/api/v1/auth/login', ['email' => 'juan@example.com', 'password' => 'correct-horse'])
        ->assertOk()
        ->assertJsonPath('data.user.email', 'juan@example.com');
});

it('revokes the current token on logout', function () {
    $user = User::factory()->create();
    $token = $user->createToken('api')->plainTextToken;

    $this->withToken($token)->postJson('/api/v1/auth/logout')->assertNoContent();

    expect($user->tokens()->count())->toBe(0);
});
