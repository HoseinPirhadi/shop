<?php

use App\Models\Category;
use App\Models\User;

test('public category listing only returns active categories', function () {
    $active = Category::factory()->create(['is_active' => true]);
    Category::factory()->create(['is_active' => false]);

    $this->getJson('/api/v1/categories')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $active->id);
});

test('public category detail is available by slug only when active', function () {
    $category = Category::factory()->create(['slug' => 'desk-accessories', 'is_active' => true]);
    $hidden = Category::factory()->create(['slug' => 'hidden-category', 'is_active' => false]);

    $this->getJson('/api/v1/categories/desk-accessories')
        ->assertOk()
        ->assertJsonPath('data.id', $category->id);

    $this->getJson('/api/v1/categories/'.$hidden->slug)
        ->assertNotFound();
});

test('guests cannot access category administration', function () {
    $this->getJson('/api/v1/admin/categories')->assertUnauthorized();
    $this->postJson('/api/v1/admin/categories', [])->assertUnauthorized();
});

test('regular users cannot access category administration', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/v1/admin/categories')
        ->assertForbidden();
});

test('administrator can create and update a category', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $parent = Category::factory()->create();

    $this->actingAs($admin)
        ->postJson('/api/v1/admin/categories', [
            'name' => 'میز و صندلی',
            'slug' => 'tables-and-chairs',
            'parent_id' => $parent->id,
            'sort_order' => 5,
            'is_active' => true,
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'میز و صندلی')
        ->assertJsonPath('data.parent_id', $parent->id);

    $category = Category::query()->where('slug', 'tables-and-chairs')->firstOrFail();

    $this->actingAs($admin)
        ->patchJson('/api/v1/admin/categories/'.$category->id, [
            'name' => 'میز و صندلی اداری',
            'is_active' => false,
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'میز و صندلی اداری')
        ->assertJsonPath('data.is_active', false);
});

test('category slug must be unique', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    Category::factory()->create(['slug' => 'unique-category']);

    $this->actingAs($admin)
        ->postJson('/api/v1/admin/categories', [
            'name' => 'دسته دوم',
            'slug' => 'unique-category',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('slug');
});

test('category cannot be assigned as its own parent or a descendant', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $parent = Category::factory()->create();
    $child = Category::factory()->create(['parent_id' => $parent->id]);

    $this->actingAs($admin)
        ->patchJson('/api/v1/admin/categories/'.$parent->id, [
            'parent_id' => $child->id,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('parent_id');

    $this->patchJson('/api/v1/admin/categories/'.$parent->id, [
        'parent_id' => $parent->id,
    ])->assertUnprocessable();
});

test('category with children cannot be deleted', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $parent = Category::factory()->create();
    Category::factory()->create(['parent_id' => $parent->id]);

    $this->actingAs($admin)
        ->deleteJson('/api/v1/admin/categories/'.$parent->id)
        ->assertConflict();

    expect($parent->fresh())->not->toBeNull();
});

test('administrator can delete a category without children', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $category = Category::factory()->create();

    $this->actingAs($admin)
        ->deleteJson('/api/v1/admin/categories/'.$category->id)
        ->assertNoContent();

    expect(Category::query()->whereKey($category->id)->exists())->toBeFalse();
});

test('administrator category listing includes inactive categories and parent metadata', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $parent = Category::factory()->create();
    $child = Category::factory()->create(['parent_id' => $parent->id, 'is_active' => false]);

    $this->actingAs($admin)
        ->getJson('/api/v1/admin/categories')
        ->assertOk()
        ->assertJsonFragment([
            'id' => $child->id,
            'parent_id' => $parent->id,
            'is_active' => false,
        ]);
});
