<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('customers can visit their dashboard without seeing store statistics', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('isAdmin', false)
            ->where('stats', null)
        );
});

test('administrators see live product category and inventory statistics', function () {
    $admin = User::factory()->create();
    $admin->forceFill(['is_admin' => true])->save();
    Product::factory()->create(['is_active' => true]);
    Product::factory()->create(['is_active' => false]);
    Category::factory()->create();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('isAdmin', true)
            ->where('stats.products', 2)
            ->where('stats.publishedProducts', 1)
            ->where('stats.categories', 1)
            ->where('stats.variants', 2)
            ->where('stats.trackedVariants', 2)
            ->where('stats.outOfStock', 2)
        );
});
