<?php

namespace App\\Http\\Requests;

use Illuminate\\Foundation\\Http\\FormRequest;
use Illuminate\\Validation\\Rule;

class StoreProductVariantRequest extends FormRequest
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
            'sku' => ['nullable', 'string', 'max:100', Rule::unique('product_variants', 'sku')],
            'options' => ['nullable', 'array', 'max:20'],
            'options.*' => ['required', 'string', 'max:100'],
            'track_inventory' => ['sometimes', 'boolean'],
            'stock_quantity' => ['sometimes', 'integer', 'min:0', 'max:4294967295'],
            'is_active' => ['sometimes', 'boolean'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}
