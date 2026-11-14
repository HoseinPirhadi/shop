<?php

use App\Models\User;

function addressPayload(array $overrides = []): array
{
    return array_merge([
        'label' => 'خانه',
        'recipient_name' => 'مشتری نمونه',
        'phone' => '+447700900123',
        'country_code' => 'GB',
        'state' => 'Greater London',
        'city' => 'London',
        'address_line1' => '10 Example Street',
        'address_line2' => 'Flat 2',
        'postal_code' => 'E1 6AN',
    ], $overrides);
}

test('customers can create addresses and their first address becomes default', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/v1/me/addresses', addressPayload());

    $response->assertCreated()
        ->assertJsonPath('data.label', 'خانه')
        ->assertJsonPath('data.country_code', 'GB')
        ->assertJsonPath('data.is_default', true);

    $this->assertDatabaseHas('customer_addresses', [
        'user_id' => $user->id,
        'label' => 'خانه',
        'is_default' => true,
    ]);
});

test('customers can update an address and change the default address', function () {
    $user = User::factory()->create();
    $first = $this->actingAs($user)
        ->postJson('/api/v1/me/addresses', addressPayload())
        ->assertCreated()
        ->json('data.id');
    $second = $this->postJson('/api/v1/me/addresses', addressPayload([
        'label' => 'محل کار',
        'is_default' => false,
    ]))->assertCreated()->json('data.id');

    $this->patchJson('/api/v1/me/addresses/'.$second, [
        'city' => 'Manchester',
        'is_default' => true,
    ])
        ->assertOk()
        ->assertJsonPath('data.city', 'Manchester')
        ->assertJsonPath('data.is_default', true);

    $this->assertDatabaseHas('customer_addresses', [
        'id' => $first,
        'is_default' => false,
    ]);
});

test('customers cannot read or modify another users addresses', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $addressId = $this->actingAs($owner)
        ->postJson('/api/v1/me/addresses', addressPayload())
        ->assertCreated()
        ->json('data.id');

    $this->actingAs($otherUser)
        ->patchJson('/api/v1/me/addresses/'.$addressId, ['city' => 'Paris'])
        ->assertNotFound();

    $this->actingAs($otherUser)
        ->deleteJson('/api/v1/me/addresses/'.$addressId)
        ->assertNotFound();
});

test('deleting the default address promotes the next address', function () {
    $user = User::factory()->create();
    $first = $this->actingAs($user)
        ->postJson('/api/v1/me/addresses', addressPayload())
        ->assertCreated()
        ->json('data.id');
    $second = $this->postJson('/api/v1/me/addresses', addressPayload([
        'label' => 'محل کار',
    ]))->assertCreated()->json('data.id');

    $this->deleteJson('/api/v1/me/addresses/'.$first)->assertNoContent();

    $this->assertDatabaseHas('customer_addresses', [
        'id' => $second,
        'is_default' => true,
    ]);
});

test('address country codes and required fields are validated', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/v1/me/addresses', addressPayload([
            'country_code' => 'gbr',
            'city' => '',
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['country_code', 'city']);
});

test('guests cannot access customer addresses', function () {
    $this->getJson('/api/v1/me/addresses')->assertUnauthorized();
});
