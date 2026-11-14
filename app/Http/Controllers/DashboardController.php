<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        if (! $request->user()->isAdmin()) {
            return Inertia::render('Dashboard', [
                'isAdmin' => false,
                'stats' => null,
                'recentProducts' => [],
                'recentMovements' => [],
            ]);
        }

        $trackedVariants = ProductVariant::query()
            ->where('is_active', true)
            ->where('track_inventory', true);

        return Inertia::render('Dashboard', [
            'isAdmin' => true,
            'stats' => [
                'products' => Product::query()->count(),
                'publishedProducts' => Product::query()->where('is_active', true)->count(),
                'categories' => Category::query()->count(),
                'variants' => ProductVariant::query()->where('is_active', true)->count(),
                'trackedVariants' => (clone $trackedVariants)->count(),
                'lowStock' => (clone $trackedVariants)->whereBetween('stock_quantity', [1, 5])->count(),
                'outOfStock' => (clone $trackedVariants)->where('stock_quantity', 0)->count(),
                'availableStock' => (clone $trackedVariants)->where('stock_quantity', '>', 5)->count(),
            ],
            'recentProducts' => Product::query()
                ->with(['images' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('sort_order')])
                ->latest()
                ->limit(5)
                ->get(['id', 'name', 'slug', 'is_active', 'created_at']),
            'recentMovements' => InventoryMovement::query()
                ->with(['product:id,name', 'variant:id,name', 'creator:id,name'])
                ->latest()
                ->limit(6)
                ->get(),
        ]);
    }
}
