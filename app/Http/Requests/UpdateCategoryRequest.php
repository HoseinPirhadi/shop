<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var Category $category */
        $category = $this->route('category');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:160'],
            'slug' => ['sometimes', 'required', 'string', 'max:180', 'alpha_dash:ascii', Rule::unique('categories', 'slug')->ignore($category->id)],
            'description' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'parent_id' => ['sometimes', 'nullable', 'integer', Rule::exists('categories', 'id')],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:4294967295'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
