<?php

use App\\Models\\InventoryMovement;
use App\\Models\\Product;
use App\\Models\\ProductVariant;
use App\\Models\\User;

test('new products receive a default inventory-tracked variant', function () {
    $product = Product::factory()->create();

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
    $default = $product->variants()->firstOrFail();

    $response = $this->actingAs($admin)->postJson('/api/v1/admin/products/'.$product->id.'/variants', [
        'name' => 'مشکی / سایز بزرگ',
        'sku' => 'LAMP-BLK-L',
        'options' => ['رنگ' => 'مشکی', 'سایز' => 'بزرگ'],
        'is_default' => true,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'مشکی / سایز بزرگ')
        ->assertJsonPath('data.stock_quantity', 0)
        ->assertJsonPath('data.is_default', true);

    expect($default->fresh()->is_default)->toBeFalse();

    $variantId = $response->json('data.id');

    $this->actingAs($admin)
        ->postJson('/api/v1/admin/products/'.$product->id.'/variants/'.$variantId.'/inventory-adjustments', [
            'quantity_delta' => 8,
            'reason' => 'موجودی اولیه',
        ])
        ->assertOk()
        ->assertJsonPath('data.stock_quantity', 8);
});

test('inventory adjustments are audited and cannot make stock negative', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $product = Product::factory()->create();
    $variant = $product->variants()->firstOrFail();
    $variant->update(['name' => 'آبی', 'stock_quantity' => 4]);

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
    $variant = $product->variants()->firstOrFail();
    $variant->update([
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
    $variant = $product->variants()->firstOrFail();

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
    $otherVariant = $otherProduct->variants()->firstOrFail();

    $this->actingAs($admin)
        ->deleteJson('/api/v1/admin/products/'.$product->id.'/variants/'.$otherVariant->id)
        ->assertNotFound();

    $onlyVariant = $product->variants()->firstOrFail();

    $this->actingAs($admin)
        ->deleteJson('/api/v1/admin/products/'.$product->id.'/variants/'.$onlyVariant->id)
        ->assertStatus(409);

    $otherVariant->inventoryMovements()->create([
        'product_id' => $otherProduct->id,
        'created_by' => $admin->id,
        'quantity_delta' => 3,
        'quantity_before' => 0,
        'quantity_after' => 3,
        'reason' => 'ورود موجودی',
    ]);

    $this->actingAs($admin)
        ->deleteJson('/api/v1/admin/products/'.$otherProduct->id.'/variants/'.$otherVariant->id)
        ->assertStatus(409);
});
