<?php

test('application uses Persian locale and renders an RTL document', function () {
    expect(app()->getLocale())->toBe('fa')
        ->and(__('auth.failed'))->toContain('اطلاعات ورود');

    $this->get('/login')
        ->assertOk()
        ->assertSee('<html lang="fa" dir="rtl">', false)
        ->assertSee('ورود به حساب');
});

test('common validation errors are translated into Persian', function () {
    expect(__('validation.required', ['attribute' => 'نشانی ایمیل']))
        ->toBe('وارد کردن فیلد نشانی ایمیل الزامی است.');
});
