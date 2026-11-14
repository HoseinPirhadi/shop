<?php

use App\Models\StoreSetting;
use App\Models\User;

test('store settings start without an assumed currency', function () {
    $this->getJson('/api/v1/store-settings')
        ->assertOk()
        ->assertJsonPath('data.currency_code', null)
        ->assertJsonPath('data.currency_configured', false)
        ->assertJsonPath('data.checkout_enabled', false);
});

test('administrators can update store name and ISO currency code format', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->patchJson('/api/v1/admin/settings/store', [
            'store_name' => 'فروشگاه نمونه',
            'currency_code' => 'GBP',
        ])
        ->assertOk()
        ->assertJsonPath('data.store_name', 'فروشگاه نمونه')
        ->assertJsonPath('data.currency_code', 'GBP')
        ->assertJsonPath('data.currency_configured', true)
        ->assertJsonPath('data.checkout_enabled', true);

    expect(StoreSetting::current()->currency_code)->toBe('GBP');
});

test('invalid currency code formats are rejected', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->patchJson('/api/v1/admin/settings/store', [
            'currency_code' => 'gbp',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('currency_code');

    $this->actingAs($admin)
        ->patchJson('/api/v1/admin/settings/store', [
            'currency_code' => 'US',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('currency_code');
});

test('customers cannot view or update administrative store settings', function () {
    $customer = User::factory()->create();

    $this->actingAs($customer)
        ->getJson('/api/v1/admin/settings/store')
        ->assertForbidden();

    $this->actingAs($customer)
        ->patchJson('/api/v1/admin/settings/store', [
            'currency_code' => 'EUR',
        ])
        ->assertForbidden();
});
