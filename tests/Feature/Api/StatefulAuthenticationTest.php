<?php

use App\Models\User;
use Symfony\Component\HttpFoundation\Cookie;

test('the first-party SPA can initialize CSRF protection', function () {
    $response = $this->withHeader('Origin', 'http://localhost:8000')
        ->get('/sanctum/csrf-cookie');

    $response->assertNoContent();

    $cookieNames = collect($response->headers->getCookies())
        ->map(fn (Cookie $cookie): string => $cookie->getName());

    expect($cookieNames)->toContain('XSRF-TOKEN');
});

test('the first-party SPA session can authenticate versioned API requests', function () {
    $user = User::factory()->create();
    $origin = 'http://localhost:8000';

    $loginResponse = $this->withHeader('Origin', $origin)
        ->postJson('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

    $loginResponse->assertNoContent();

    $sessionCookie = collect($loginResponse->headers->getCookies())
        ->first(fn (Cookie $cookie): bool => $cookie->getName() === config('session.cookie'));

    expect($sessionCookie)->not->toBeNull();

    $this->withCookie(config('session.cookie'), $sessionCookie->getValue())
        ->withHeader('Origin', $origin)
        ->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.email', $user->email);
});
