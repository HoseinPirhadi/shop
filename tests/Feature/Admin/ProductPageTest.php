<?php

use App\Models\User;

test('guests are redirected away from product management', function () {
    $this->get('/admin/products')
        ->assertRedirect('/login');
});

test('regular customers cannot open product management', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin/products')
        ->assertForbidden();
});

test('administrators can open product management', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get('/admin/products')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Admin/Products/Index'));
});
