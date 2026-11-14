<?php

use App\Models\User;

test('guests are redirected away from category management', function () {
    $this->get('/admin/categories')
        ->assertRedirect('/login');
});

test('regular customers cannot open category management', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin/categories')
        ->assertForbidden();
});

test('administrators can open category management', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get('/admin/categories')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Admin/Categories/Index'));
});
