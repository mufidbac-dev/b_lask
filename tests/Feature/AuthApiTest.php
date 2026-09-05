<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

it('registers a user without exposing a token before email verification', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Personal User',
        'email' => 'personal@example.com',
        'password' => 'correct horse battery staple',
        'password_confirmation' => 'correct horse battery staple',
        'device_name' => 'browser',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.email', 'personal@example.com')
        ->assertJsonMissingPath('token');

    expect(User::where('email', 'personal@example.com')->exists())->toBeTrue();
});

it('logs in a verified user and returns a scoped token', function () {
    $user = User::factory()->create([
        'email' => 'verified@example.com',
        'password' => Hash::make('correct horse battery staple'),
        'email_verified_at' => now(),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'correct horse battery staple',
        'device_name' => 'test-client',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('token_type', 'Bearer')
        ->assertJsonStructure(['data', 'token', 'token_type']);

    expect($user->tokens()->first()->name)->toBe('test-client');
});

it('rejects unverified users at login', function () {
    $user = User::factory()->unverified()->create([
        'password' => Hash::make('correct horse battery staple'),
    ]);

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'correct horse battery staple',
    ])->assertForbidden();
});

it('returns the authenticated profile and revokes the current token on logout', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-client', ['profile:read']);

    $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
        ->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.email', $user->email);

    $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
        ->postJson('/api/v1/auth/logout')
        ->assertOk();

    expect($user->tokens()->count())->toBe(0);
});

it('enforces token abilities for profile access', function () {
    $user = User::factory()->create();
    $token = $user->createToken('limited-client', ['profile:write']);

    $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
        ->getJson('/api/v1/auth/me')
        ->assertForbidden();
});

it('rejects invalid credentials without revealing whether the email exists', function () {
    User::factory()->create([
        'email' => 'known@example.com',
        'password' => Hash::make('correct horse battery staple'),
    ]);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'known@example.com',
        'password' => 'wrong password',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('errors.email.0', 'The provided credentials are incorrect.');
});

it('generates a password reset URL without a missing named route error', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'reset@example.com']);

    $this->postJson('/api/v1/auth/forgot-password', [
        'email' => $user->email,
    ])->assertOk();

    Notification::assertSentTo($user, function (ResetPassword $notification) use ($user): bool {
        $url = $notification->toMail($user)->actionUrl;

        return str_contains($url, '/password/reset/')
            && str_contains($url, 'email='.urlencode($user->email));
    });
});

it('resets a password and revokes all existing tokens', function () {
    $user = User::factory()->create([
        'email' => 'reset-flow@example.com',
        'password' => 'old password value',
    ]);
    $user->createToken('old-device');
    $token = Password::broker()->createToken($user);

    $this->postJson('/api/v1/auth/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new secure password',
        'password_confirmation' => 'new secure password',
    ])->assertOk();

    expect(Hash::check('new secure password', $user->refresh()->password))->toBeTrue()
        ->and($user->tokens()->count())->toBe(0);
});
