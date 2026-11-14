<?php

use App\Models\User;

test('administrator access can be granted after confirmation', function () {
    $user = User::factory()->create();

    $this->artisan('app:admin:set', [
        'email' => $user->email,
        'status' => 'grant',
    ])
        ->expectsConfirmation("Are you sure you want to grant administrator access to [{$user->email}]?", 'yes')
        ->expectsOutputToContain("Administrator access granted to [{$user->email}].")
        ->assertExitCode(0);

    expect($user->fresh()->isAdmin())->toBeTrue();
});

test('administrator access can be revoked when another administrator remains', function () {
    $user = User::factory()->create();
    $otherAdministrator = User::factory()->create();

    $user->forceFill(['is_admin' => true])->save();
    $otherAdministrator->forceFill(['is_admin' => true])->save();

    $this->artisan('app:admin:set', [
        'email' => $user->email,
        'status' => 'revoke',
    ])
        ->expectsConfirmation("Are you sure you want to revoke administrator access from [{$user->email}]?", 'yes')
        ->expectsOutputToContain("Administrator access revoked from [{$user->email}].")
        ->assertExitCode(0);

    expect($user->fresh()->isAdmin())->toBeFalse()
        ->and($otherAdministrator->fresh()->isAdmin())->toBeTrue();
});

test('the last administrator cannot be revoked', function () {
    $user = User::factory()->create();
    $user->forceFill(['is_admin' => true])->save();

    $this->artisan('app:admin:set', [
        'email' => $user->email,
        'status' => 'revoke',
    ])
        ->expectsOutputToContain('The last administrator cannot be revoked.')
        ->assertExitCode(1);

    expect($user->fresh()->isAdmin())->toBeTrue();
});

test('administrator access changes are not made when confirmation is declined', function () {
    $user = User::factory()->create();

    $this->artisan('app:admin:set', [
        'email' => $user->email,
        'status' => 'grant',
    ])
        ->expectsConfirmation("Are you sure you want to grant administrator access to [{$user->email}]?", 'no')
        ->expectsOutputToContain('No changes were made.')
        ->assertExitCode(0);

    expect($user->fresh()->isAdmin())->toBeFalse();
});

test('the administrator command rejects unknown users and invalid statuses', function () {
    $this->artisan('app:admin:set', [
        'email' => 'missing@example.com',
        'status' => 'grant',
    ])
        ->expectsOutputToContain('No user was found with the email [missing@example.com].')
        ->assertExitCode(1);

    $user = User::factory()->create();

    $this->artisan('app:admin:set', [
        'email' => $user->email,
        'status' => 'promote',
    ])
        ->expectsOutputToContain('The status must be either "grant" or "revoke".')
        ->assertExitCode(2);
});
