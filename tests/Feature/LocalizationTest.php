<?php

test('application uses Persian locale and renders an RTL document', function () {
    expect(app()->getLocale())->toBe('fa')
        ->and(__('auth.failed'))->toContain('اطلاعات ورود');

    $this->get('/login')
        ->assertOk()
        ->assertSee('lang="fa" dir="rtl"', false)
        ->assertSee('ورود');
});

test('common validation errors are translated into Persian', function () {
    expect(__('validation.required', ['attribute' => 'نشانی ایمیل']))
        ->toBe('وارد کردن نشانی ایمیل الزامی است.');
});
