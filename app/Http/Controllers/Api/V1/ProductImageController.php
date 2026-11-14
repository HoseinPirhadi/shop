<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductImageRequest;
use App\Http\Requests\UpdateProductImageRequest;
use App\Http\Resources\ProductImageResource;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class ProductImageController extends Controller
{
    public function store(StoreProductImageRequest $request, Product $product): JsonResponse
    {
        $data = $request->validated();
        $path = $request->file('image')->store('products/'.$product->id, 'public');

        $image = $product->images()->create([
            'path' => $path,
            'alt' => $data['alt'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_primary' => ! $product->images()->exists(),
        ]);

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

        if (($data['is_primary'] ?? false) === true) {
            $product->images()
                ->where('id', '<>', $image->id)
                ->update(['is_primary' => false]);
        }

        $image->update($data);

        return new ProductImageResource($image->fresh());
    }

    public function destroy(Product $product, ProductImage $image): JsonResponse
    {
        abort_unless($image->product_id === $product->id, 404);

        $wasPrimary = $image->is_primary;
        Storage::disk('public')->delete($image->path);
        $image->delete();

        if ($wasPrimary) {
            $product->images()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->first()
                ?->update(['is_primary' => true]);
        }

        return ProductImageResource::collection(
            $product->images()->orderBy('sort_order')->orderBy('id')->get()
        )->response();
    }
}
