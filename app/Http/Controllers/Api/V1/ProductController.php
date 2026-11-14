<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $products = Product::query()
            ->with(['categories' => fn ($query) => $query->where('is_active', true)])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(30);

        return ProductResource::collection($products);
    }

    public function show(string $slug): ProductResource|JsonResponse
    {
        $product = Product::query()
            ->with(['categories' => fn ($query) => $query->where('is_active', true)])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if ($product === null) {
            return response()->json([
                'message' => 'محصول موردنظر پیدا نشد.',
            ], 404);
        }

        return new ProductResource($product);
    }

    public function adminIndex(): AnonymousResourceCollection
    {
        $products = Product::query()
            ->with('categories')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(30);

        return ProductResource::collection($products);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $data = $request->validated();
        $categoryIds = $data['category_ids'] ?? [];
        unset($data['category_ids']);

        $product = Product::query()->create($data);
        $product->categories()->sync($categoryIds);

        return (new ProductResource($product->load('categories')))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        $data = $request->validated();

        if (array_key_exists('category_ids', $data)) {
            $product->categories()->sync($data['category_ids']);
            unset($data['category_ids']);
        }

        $product->update($data);

        return new ProductResource($product->load('categories'));
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json(status: 204);
    }
}
