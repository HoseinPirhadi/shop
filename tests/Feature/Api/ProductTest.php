<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;

test('public product listing only returns active products', function () {
    $active = Product::factory()->create(['is_active' => true]);
    Product::factory()->create(['is_active' => false]);

    $this->getJson('/api/v1/products')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $active->id);
});

test('public product detail is available by slug only when active', function () {
    $product = Product::factory()->create(['slug' => 'desk-lamp', 'is_active' => true]);
    $hidden = Product::factory()->create(['slug' => 'draft-product', 'is_active' => false]);

    $this->getJson('/api/v1/products/desk-lamp')
        ->assertOk()
        ->assertJsonPath('data.id', $product->id);

    $this->getJson('/api/v1/products/'.$hidden->slug)
        ->assertNotFound();
});

test('guests and regular users cannot access product administration', function () {
    $this->getJson('/api/v1/admin/products')->assertUnauthorized();
    $this->postJson('/api/v1/admin/products', [])->assertUnauthorized();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/v1/admin/products')
        ->assertForbidden();
});

test('administrator can create and update a product with categories', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $category = Category::factory()->create();

    $this->actingAs($admin)
        ->postJson('/api/v1/admin/products', [
            'name' => 'چراغ مطالعه',
            'slug' => 'desk-lamp',
            'description' => 'چراغ مطالعه رومیزی',
            'category_ids' => [$category->id],
            'is_active' => true,
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'چراغ مطالعه')
        ->assertJsonPath('data.is_active', true)
        ->assertJsonPath('data.categories.0.id', $category->id);

    $product = Product::query()->where('slug', 'desk-lamp')->firstOrFail();

    $this->actingAs($admin)
        ->patchJson('/api/v1/admin/products/'.$product->id, [
            'name' => 'چراغ مطالعه LED',
            'category_ids' => [],
            'is_active' => false,
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'چراغ مطالعه LED')
        ->assertJsonPath('data.categories', [])
        ->assertJsonPath('data.is_active', false);
});

test('product slug must be unique and category ids must exist', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    Product::factory()->create(['slug' => 'existing-product']);

    $this->actingAs($admin)
        ->postJson('/api/v1/admin/products', [
            'name' => 'محصول تکراری',
            'slug' => 'existing-product',
            'category_ids' => [999999],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['slug', 'category_ids.0']);
});

test('administrator can delete a product', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $product = Product::factory()->create();

    $this->actingAs($admin)
        ->deleteJson('/api/v1/admin/products/'.$product->id)
        ->assertNoContent();

    expect(Product::query()->whereKey($product->id)->exists())->toBeFalse();
});

test('administrator product listing includes inactive products and categories', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $category = Category::factory()->create();
    $product = Product::factory()->create(['is_active' => false]);
    $product->categories()->attach($category);

    $this->actingAs($admin)
        ->getJson('/api/v1/admin/products')
        ->assertOk()
        ->assertJsonFragment([
            'id' => $product->id,
            'is_active' => false,
        ])
        ->assertJsonPath('data.0.categories.0.id', $category->id);
});
