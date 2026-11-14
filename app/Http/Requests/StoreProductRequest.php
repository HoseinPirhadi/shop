<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:160'],
            'slug' => ['required', 'string', 'max:180', 'alpha_dash:ascii', Rule::unique('products', 'slug')],
            'description' => ['nullable', 'string', 'max:10000'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:4294967295'],
            'is_active' => ['sometimes', 'boolean'],
            'category_ids' => ['sometimes', 'array'],
            'category_ids.*' => ['integer', 'distinct', Rule::exists('categories', 'id')],
        ];
    }
}
