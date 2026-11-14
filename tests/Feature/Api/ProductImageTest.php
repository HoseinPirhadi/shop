<?php

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('administrator can upload images, choose a primary image, and delete images', function () {
    Storage::fake('public');

    $admin = User::factory()->create(['is_admin' => true]);
    $product = Product::factory()->create(['is_active' => true]);
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADUlEQVR4nGP4z8AAAAMBAQDJ/pLvAAAAAElFTkSuQmCC') ?: '';

    $firstUpload = $this->actingAs($admin)->post(
        '/api/v1/admin/products/'.$product->id.'/images',
        [
            'image' => UploadedFile::fake()->createWithContent('front.png', $png),
            'alt' => 'نمای جلو',
        ],
        ['Accept' => 'application/json'],
    );

    $firstUpload
        ->assertCreated()
        ->assertJsonPath('data.alt', 'نمای جلو')
        ->assertJsonPath('data.is_primary', true);

    $firstImage = ProductImage::query()->where('product_id', $product->id)->firstOrFail();
    Storage::disk('public')->assertExists($firstImage->path);

    $secondUpload = $this->actingAs($admin)->post(
        '/api/v1/admin/products/'.$product->id.'/images',
        [
            'image' => UploadedFile::fake()->createWithContent('back.png', $png),
            'alt' => 'نمای پشت',
        ],
        ['Accept' => 'application/json'],
    );

    $secondUpload->assertCreated()->assertJsonPath('data.is_primary', false);

    $secondImage = ProductImage::query()
        ->where('product_id', $product->id)
        ->where('id', '<>', $firstImage->id)
        ->firstOrFail();

    $this->actingAs($admin)
        ->patchJson('/api/v1/admin/products/'.$product->id.'/images/'.$secondImage->id, [
            'is_primary' => true,
        ])
        ->assertOk()
        ->assertJsonPath('data.is_primary', true);

    expect($firstImage->fresh()->is_primary)->toBeFalse();

    $this->getJson('/api/v1/products')
        ->assertOk()
        ->assertJsonPath('data.0.images.0.id', $secondImage->id);

    $this->actingAs($admin)
        ->deleteJson('/api/v1/admin/products/'.$product->id.'/images/'.$secondImage->id)
        ->assertOk()
        ->assertJsonCount(1, 'data');

    Storage::disk('public')->assertMissing($secondImage->path);
    expect($firstImage->fresh()->is_primary)->toBeTrue();

    $this->actingAs($admin)
        ->deleteJson('/api/v1/admin/products/'.$product->id.'/images/'.$firstImage->id)
        ->assertOk()
        ->assertJsonCount(0, 'data');

    Storage::disk('public')->assertMissing($firstImage->path);
});

test('guests and regular users cannot upload product images', function () {
    $product = Product::factory()->create();
    $url = '/api/v1/admin/products/'.$product->id.'/images';

    $this->post($url, [], ['Accept' => 'application/json'])->assertUnauthorized();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post($url, [], ['Accept' => 'application/json'])
        ->assertForbidden();
});

test('public product details include image metadata for active products', function () {
    Storage::fake('public');

    $product = Product::factory()->create([
        'slug' => 'desk-lamp-with-image',
        'is_active' => true,
    ]);
    $path = 'products/'.$product->id.'/front.jpg';
    Storage::disk('public')->put($path, 'fake-image');

    $product->images()->create([
        'path' => $path,
        'alt' => 'چراغ از روبه‌رو',
        'sort_order' => 0,
        'is_primary' => true,
    ]);

    $this->getJson('/api/v1/products/'.$product->slug)
        ->assertOk()
        ->assertJsonPath('data.images.0.alt', 'چراغ از روبه‌رو')
        ->assertJsonPath('data.images.0.is_primary', true)
        ->assertJsonPath('data.images.0.url', Storage::disk('public')->url($path));
});

test('deleting a product also removes its stored image files', function () {
    Storage::fake('public');

    $admin = User::factory()->create(['is_admin' => true]);
    $product = Product::factory()->create();
    $path = 'products/'.$product->id.'/image.jpg';
    Storage::disk('public')->put($path, 'fake-image');

    $product->images()->create([
        'path' => $path,
        'is_primary' => true,
    ]);

    $this->actingAs($admin)
        ->deleteJson('/api/v1/admin/products/'.$product->id)
        ->assertNoContent();

    Storage::disk('public')->assertMissing($path);
});

test('primary images cannot be unset and images cannot be managed through another product', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $product = Product::factory()->create();
    $anotherProduct = Product::factory()->create();
    $image = $product->images()->create([
        'path' => 'products/'.$product->id.'/front.jpg',
        'is_primary' => true,
    ]);

    $this->actingAs($admin)
        ->patchJson('/api/v1/admin/products/'.$product->id.'/images/'.$image->id, [
            'is_primary' => false,
        ])
        ->assertUnprocessable();

    $this->actingAs($admin)
        ->patchJson('/api/v1/admin/products/'.$anotherProduct->id.'/images/'.$image->id, [
            'alt' => 'تصویر نامعتبر',
        ])
        ->assertNotFound();

    $this->actingAs($admin)
        ->deleteJson('/api/v1/admin/products/'.$anotherProduct->id.'/images/'.$image->id)
        ->assertNotFound();

    expect($image->fresh()->is_primary)->toBeTrue();
});

test('image uploads reject files outside the allowed image formats', function () {
    Storage::fake('public');

    $admin = User::factory()->create(['is_admin' => true]);
    $product = Product::factory()->create();

    $this->actingAs($admin)
        ->post('/api/v1/admin/products/'.$product->id.'/images', [
            'image' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
        ], ['Accept' => 'application/json'])
        ->assertUnprocessable();

    expect(ProductImage::query()->where('product_id', $product->id)->count())->toBe(0);
    expect(Storage::disk('public')->allFiles('products/'.$product->id))->toBe([]);
});
