<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return CategoryResource::collection($categories);
    }

    public function show(string $slug): CategoryResource|JsonResponse
    {
        $category = Category::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if ($category === null) {
            return response()->json([
                'message' => 'دسته‌بندی موردنظر پیدا نشد.',
            ], 404);
        }

        return new CategoryResource($category);
    }

    public function adminIndex(): AnonymousResourceCollection
    {
        $categories = Category::query()
            ->with('parent')
            ->withCount('children')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(30);

        return CategoryResource::collection($categories);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = Category::query()->create($request->validated());

        return (new CategoryResource($category))->response()->setStatusCode(201);
    }

    public function update(UpdateCategoryRequest $request, Category $category): CategoryResource|JsonResponse
    {
        $data = $request->validated();

        if (array_key_exists('parent_id', $data) && $data['parent_id'] !== null) {
            $parentId = (int) $data['parent_id'];

            if ($parentId === $category->id || $this->isDescendant($category, $parentId)) {
                return response()->json([
                    'message' => 'انتخاب این دسته‌بندی به‌عنوان والد مجاز نیست.',
                    'errors' => [
                        'parent_id' => ['ساختار دسته‌بندی نباید دارای چرخه باشد.'],
                    ],
                ], 422);
            }
        }

        $category->update($data);

        return new CategoryResource($category->fresh());
    }

    public function destroy(Category $category): JsonResponse
    {
        if ($category->children()->exists()) {
            return response()->json([
                'message' => 'ابتدا زیردسته‌های این دسته‌بندی را منتقل یا حذف کنید.',
            ], 409);
        }

        $category->delete();

        return response()->json(status: 204);
    }

    private function isDescendant(Category $category, int $candidateParentId): bool
    {
        $parentId = $candidateParentId;

        while ($parentId > 0) {
            if ($parentId === $category->id) {
                return true;
            }

            $nextParentId = Category::query()
                ->whereKey($parentId)
                ->value('parent_id');

            if ($nextParentId === null) {
                return false;
            }

            $parentId = (int) $nextParentId;
        }

        return false;
    }
}
