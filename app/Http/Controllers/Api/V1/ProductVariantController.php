<?php

namespace App\\Http\\Controllers\\Api\\V1;

use App\\Http\\Controllers\\Controller;
use App\\Http\\Requests\\StoreProductVariantRequest;
use App\\Http\\Requests\\UpdateProductVariantRequest;
use App\\Http\\Resources\\ProductVariantMovementResource;
use App\\Http\\Resources\\ProductVariantResource;
use App\\Models\\InventoryMovement;
use App\\Models\\Product;
use App\\Models\\ProductVariant;
use Illuminate\\Http\\JsonResponse;
use Illuminate\\Http\\Request;
use Illuminate\\Http\\Resources\\Json\\AnonymousResourceCollection;
use Illuminate\\Support\\Facades\\DB;

class ProductVariantController extends Controller
{
    public function index(Product $product): AnonymousResourceCollection
    {
        return ProductVariantResource::collection(
            $product->variants()->orderByDesc('is_default')->orderBy('id')->get()
        );
    }

    public function store(StoreProductVariantRequest $request, Product $product): JsonResponse
    {
        $data = $request->validated();
        $variant = DB::transaction(function () use ($data, $product): ProductVariant {
            $lockedProduct = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();

            if ($data['is_default'] ?? false) {
                $lockedProduct->variants()->update(['is_default' => false]);
            }

            return $lockedProduct->variants()->create($data);
        });

        return (new ProductVariantResource($variant))
            ->response()
            ->setStatusCode(201);
    }

    public function update(
        UpdateProductVariantRequest $request,
        Product $product,
        ProductVariant $variant,
    ): ProductVariantResource {
        abort_unless($variant->product_id === $product->id, 404);

        $data = $request->validated();
        $updated = DB::transaction(function () use ($data, $product, $variant): ProductVariant {
            $lockedProduct = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();
            $lockedVariant = $lockedProduct->variants()->whereKey($variant->id)->lockForUpdate()->firstOrFail();

            if (array_key_exists('is_default', $data)) {
                if ($data['is_default']) {
                    $lockedProduct->variants()->where('id', '<>', $lockedVariant->id)->update(['is_default' => false]);
                } elseif ($lockedVariant->is_default) {
                    $lockedProduct->variants()
                        ->where('id', '<>', $lockedVariant->id)
                        ->orderBy('id')
                        ->first()
                        ?->update(['is_default' => true]);
                }
            }

            $lockedVariant->update($data);

            return $lockedVariant->refresh();
        });

        return new ProductVariantResource($updated);
    }

    public function destroy(Product $product, ProductVariant $variant): JsonResponse
    {
        abort_unless($variant->product_id === $product->id, 404);

        DB::transaction(function () use ($product, $variant): void {
            $lockedProduct = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();
            $lockedVariant = $lockedProduct->variants()->whereKey($variant->id)->lockForUpdate()->firstOrFail();

            if ($lockedProduct->variants()->count() <= 1) {
                abort(409, 'هر محصول باید حداقل یک گونه داشته باشد.');
            }

            $wasDefault = $lockedVariant->is_default;
            $lockedVariant->delete();

            if ($wasDefault) {
                $lockedProduct->variants()->orderBy('id')->first()?->update(['is_default' => true]);
            }
        });

        return response()->json(status: 204);
    }

    public function adjustInventory(Request $request, Product $product, ProductVariant $variant): ProductVariantResource
    {
        abort_unless($variant->product_id === $product->id, 404);

        $data = $request->validate([
            'quantity_delta' => ['required', 'integer', 'not_in:0', 'min:-4294967295', 'max:4294967295'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $updated = DB::transaction(function () use ($data, $product, $variant, $request): ProductVariant {
            $lockedProduct = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();
            $lockedVariant = $lockedProduct->variants()->whereKey($variant->id)->lockForUpdate()->firstOrFail();

            if (! $lockedVariant->track_inventory) {
                abort(422, 'برای این گونه، کنترل موجودی غیرفعال است.');
            }

            $before = $lockedVariant->stock_quantity;
            $after = $before + $data['quantity_delta'];

            if ($after < 0) {
                abort(422, 'موجودی نمی‌تواند منفی شود.');
            }

            if ($after > 4294967295) {
                abort(422, 'موجودی از محدوده مجاز بیشتر است.');
            }

            $lockedVariant->update(['stock_quantity' => $after]);

            InventoryMovement::query()->create([
                'product_id' => $lockedProduct->id,
                'product_variant_id' => $lockedVariant->id,
                'created_by' => $request->user()?->id,
                'quantity_delta' => $data['quantity_delta'],
                'quantity_before' => $before,
                'quantity_after' => $after,
                'reason' => $data['reason'],
            ]);

            return $lockedVariant->refresh();
        });

        return new ProductVariantResource($updated);
    }

    public function movements(Product $product, ProductVariant $variant): AnonymousResourceCollection
    {
        abort_unless($variant->product_id === $product->id, 404);

        return ProductVariantMovementResource::collection(
            $variant->inventoryMovements()->latest('id')->paginate(30)
        );
    }
}
