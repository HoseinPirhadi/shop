<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
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
        /** @var Product $product */
        $product = $this->route('product');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:160'],
            'slug' => ['sometimes', 'required', 'string', 'max:180', 'alpha_dash:ascii', Rule::unique('products', 'slug')->ignore($product->id)],
            'description' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:4294967295'],
            'is_active' => ['sometimes', 'boolean'],
            'category_ids' => ['sometimes', 'array'],
            'category_ids.*' => ['integer', 'distinct', Rule::exists('categories', 'id')],
        ];
    }
}
