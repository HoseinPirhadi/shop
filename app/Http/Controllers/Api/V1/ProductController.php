<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'category' => ['sometimes', 'string', 'max:180'],
            'q' => ['sometimes', 'string', 'max:120'],
            'sort' => ['sometimes', 'string', Rule::in(['featured', 'newest', 'name_asc', 'name_desc'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:48'],
        ]);

        $query = Product::query()
            ->with([
                'categories' => fn ($query) => $query->where('is_active', true),
                'images' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id'),
                'variants' => fn ($query) => $query->where('is_active', true)->orderByDesc('is_default')->orderBy('id'),
            ])
            ->where('is_active', true);

        if (! empty($filters['category'])) {
            $query->whereHas('categories', fn ($categories) => $categories
                ->where('slug', $filters['category'])
                ->where('is_active', true));
        }

        if (! empty($filters['q'])) {
            $search = trim($filters['q']);

            if ($search !== '') {
                $query->where(fn ($products) => $products
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%'));
            }
        }

        match ($filters['sort'] ?? 'featured') {
            'newest' => $query->orderByDesc('created_at')->orderByDesc('id'),
            'name_asc' => $query->orderBy('name')->orderBy('id'),
            'name_desc' => $query->orderByDesc('name')->orderByDesc('id'),
            default => $query->orderBy('sort_order')->orderBy('name')->orderBy('id'),
        };

        return ProductResource::collection(
            $query->paginate((int) ($filters['per_page'] ?? 12))->withQueryString()
        );
    }

    public function show(string $slug): ProductResource|JsonResponse
    {
        $product = Product::query()
            ->with([
                'categories' => fn ($query) => $query->where('is_active', true),
                'images' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id'),
                'variants' => fn ($query) => $query->where('is_active', true)->orderByDesc('is_default')->orderBy('id'),
            ])
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
            ->with([
                'categories',
                'images' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id'),
                'variants' => fn ($query) => $query->orderByDesc('is_default')->orderBy('id'),
            ])
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

        return (new ProductResource($product->load(['categories', 'images', 'variants'])))
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

        return new ProductResource($product->load(['categories', 'images', 'variants']));
    }

    public function destroy(Product $product): JsonResponse
    {
        $imagePaths = DB::transaction(function () use ($product): array {
            $lockedProduct = Product::query()
                ->whereKey($product->id)
                ->lockForUpdate()
                ->firstOrFail();

            $paths = $lockedProduct->images()->pluck('path')->all();
            $lockedProduct->delete();

            return $paths;
        });

        Storage::disk('public')->delete($imagePaths);

        return response()->json(status: 204);
    }
}
