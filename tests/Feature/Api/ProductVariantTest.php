<?php

use App\\Models\\InventoryMovement;
use App\\Models\\Product;
use App\\Models\\ProductVariant;
use App\\Models\\User;

test('existing products receive a default inventory-tracked variant', function () {
    $product = Product::factory()->create();

    $this->artisan('migrate');

    $this->assertDatabaseHas('product_variants', [
        'product_id' => $product->id,
        'name' => 'پیش‌فرض',
        'stock_quantity' => 0,
        'track_inventory' => true,
        'is_default' => true,
    ]);
});

test('administrators can create variants and choose one default variant', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $product = Product::factory()->create();
    $default = ProductVariant::query()->create([
        'product_id' => $product->id,
        'name' => 'پیش‌فرض',
        'is_default' => true,
    ]);

    $response = $this->actingAs($admin)->postJson('/api/v1/admin/products/'.$product->id.'/variants', [
        'name' => 'مشکی / سایز بزرگ',
        'sku' => 'LAMP-BLK-L',
        'options' => ['رنگ' => 'مشکی', 'سایز' => 'بزرگ'],
        'stock_quantity' => 8,
        'is_default' => true,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'مشکی / سایز بزرگ')
        ->assertJsonPath('data.stock_quantity', 8)
        ->assertJsonPath('data.is_default', true);

    expect($default->fresh()->is_default)->toBeFalse();
});

test('inventory adjustments are audited and cannot make stock negative', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $product = Product::factory()->create();
    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'name' => 'آبی',
        'stock_quantity' => 4,
        'track_inventory' => true,
    ]);

    $this->actingAs($admin)
        ->postJson('/api/v1/admin/products/'.$product->id.'/variants/'.$variant->id.'/inventory-adjustments', [
            'quantity_delta' => -2,
            'reason' => 'فروش حضوری',
        ])
        ->assertOk()
        ->assertJsonPath('data.stock_quantity', 2);

    $this->assertDatabaseHas('inventory_movements', [
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'created_by' => $admin->id,
        'quantity_delta' => -2,
        'quantity_before' => 4,
        'quantity_after' => 2,
        'reason' => 'فروش حضوری',
    ]);

    $this->actingAs($admin)
        ->postJson('/api/v1/admin/products/'.$product->id.'/variants/'.$variant->id.'/inventory-adjustments', [
            'quantity_delta' => -3,
            'reason' => 'اصلاح',
        ])
        ->assertUnprocessable();

    expect($variant->fresh()->stock_quantity)->toBe(2);
    expect(InventoryMovement::query()->count())->toBe(1);
});

test('variant inventory is not exposed to public product responses', function () {
    $product = Product::factory()->create(['is_active' => true]);
    ProductVariant::query()->create([
        'product_id' => $product->id,
        'name' => 'سفید',
        'sku' => 'SECRET-SKU',
        'stock_quantity' => 17,
        'track_inventory' => true,
    ]);

    $this->getJson('/api/v1/products/'.$product->slug)
        ->assertOk()
        ->assertJsonPath('data.variants.0.name', 'سفید')
        ->assertJsonMissingPath('data.variants.0.sku')
        ->assertJsonMissingPath('data.variants.0.stock_quantity');
});

test('customers cannot manage variants or inventory', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();
    $variant = ProductVariant::query()->create(['product_id' => $product->id, 'name' => 'پیش‌فرض']);

    $this->actingAs($user)
        ->getJson('/api/v1/admin/products/'.$product->id.'/variants')
        ->assertForbidden();

    $this->actingAs($user)
        ->postJson('/api/v1/admin/products/'.$product->id.'/variants/'.$variant->id.'/inventory-adjustments', [
            'quantity_delta' => 1,
            'reason' => 'test',
        ])
        ->assertForbidden();
});

test('a product must keep at least one variant and nested ownership is enforced', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $product = Product::factory()->create();
    $otherProduct = Product::factory()->create();
    $variant = ProductVariant::query()->create(['product_id' => $otherProduct->id, 'name' => 'other']);

    $this->actingAs($admin)
        ->deleteJson('/api/v1/admin/products/'.$product->id.'/variants/'.$variant->id)
        ->assertNotFound();

    $onlyVariant = ProductVariant::query()->create(['product_id' => $product->id, 'name' => 'only']);

    $this->actingAs($admin)
        ->deleteJson('/api/v1/admin/products/'.$product->id.'/variants/'.$onlyVariant->id)
        ->assertStatus(409);
});
