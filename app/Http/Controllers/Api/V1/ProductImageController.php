<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductImageRequest;
use App\Http\Requests\UpdateProductImageRequest;
use App\Http\Resources\ProductImageResource;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProductImageController extends Controller
{
    public function store(StoreProductImageRequest $request, Product $product): JsonResponse
    {
        $data = $request->validated();
        $file = $request->file('image');

        if (! $file instanceof UploadedFile) {
            abort(422, 'تصویر معتبر ارسال نشده است.');
        }

        $path = $file->store('products/'.$product->id, 'public');

        if ($path === false) {
            abort(500, 'ذخیره تصویر انجام نشد.');
        }

        try {
            $image = DB::transaction(function () use ($data, $path, $product): ProductImage {
                $lockedProduct = Product::query()
                    ->whereKey($product->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                return $lockedProduct->images()->create([
                    'path' => $path,
                    'alt' => $data['alt'] ?? null,
                    'sort_order' => $data['sort_order'] ?? 0,
                    'is_primary' => ! $lockedProduct->images()->exists(),
                ]);
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($path);

            throw $exception;
        }

        return (new ProductImageResource($image))
            ->response()
            ->setStatusCode(201);
    }

    public function update(
        UpdateProductImageRequest $request,
        Product $product,
        ProductImage $image,
    ): ProductImageResource {
        abort_unless($image->product_id === $product->id, 404);

        $data = $request->validated();

        DB::transaction(function () use ($data, $product, $image): void {
            Product::query()
                ->whereKey($product->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (array_key_exists('is_primary', $data)) {
                $product->images()
                    ->where('id', '<>', $image->id)
                    ->update(['is_primary' => false]);
                $data['is_primary'] = true;
            }

            $image->update($data);
        });

        return new ProductImageResource($image->fresh());
    }

    public function destroy(Product $product, ProductImage $image): JsonResponse
    {
        abort_unless($image->product_id === $product->id, 404);

        $path = $image->path;

        DB::transaction(function () use ($image, $product): void {
            Product::query()
                ->whereKey($product->id)
                ->lockForUpdate()
                ->firstOrFail();

            $wasPrimary = $image->is_primary;
            $image->delete();

            if ($wasPrimary) {
                $product->images()
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->first()
                    ?->update(['is_primary' => true]);
            }
        });

        Storage::disk('public')->delete($path);

        return ProductImageResource::collection(
            $product->images()
                ->orderByDesc('is_primary')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
        )->response();
    }
}
