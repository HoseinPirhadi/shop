<?php

use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('the current user endpoint rejects guests', function () {
    $this->getJson('/api/v1/me')
        ->assertUnauthorized();
});

test('an authenticated user can retrieve their safe profile', function () {
    $user = User::factory()->create([
        'name' => 'Test Customer',
        'email' => 'customer@example.com',
    ]);

    $this->actingAs($user)
        ->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.name', 'Test Customer')
        ->assertJsonPath('data.email', 'customer@example.com')
        ->assertJsonPath('data.is_admin', false)
        ->assertJsonMissingPath('data.password');
});

test('regular users cannot access the administrator ability', function () {
    $user = User::factory()->create();

    expect(Gate::forUser($user)->allows('access-admin'))->toBeFalse();
});

test('administrators can access the administrator ability', function () {
    $user = User::factory()->create();
    $user->forceFill(['is_admin' => true])->save();

    expect(Gate::forUser($user)->allows('access-admin'))->toBeTrue();
});

test('administrator status cannot be mass assigned', function () {
    $user = User::factory()->create();

    $user->fill(['is_admin' => true]);

    expect($user->fresh()->is_admin)->toBeFalse();
});
