<?php

test('storefront home page is available to guests', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Store/Index'));
});

test('category storefront page receives its slug', function () {
    $this->get('/categories/home-office')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Store/Categories/Show')
            ->where('slug', 'home-office'));
});

test('product storefront page receives its slug', function () {
    $this->get('/products/desk-lamp')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Store/Products/Show')
            ->where('slug', 'desk-lamp'));
});
