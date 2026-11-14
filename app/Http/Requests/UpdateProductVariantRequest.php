<?php

namespace App\\Http\\Requests;

use App\\Models\\ProductVariant;
use Illuminate\\Foundation\\Http\\FormRequest;
use Illuminate\\Validation\\Rule;

class UpdateProductVariantRequest extends FormRequest
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
        /** @var ProductVariant $variant */
        $variant = $this->route('variant');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:160'],
            'sku' => ['sometimes', 'nullable', 'string', 'max:100', Rule::unique('product_variants', 'sku')->ignore($variant->id)],
            'options' => ['sometimes', 'nullable', 'array', 'max:20'],
            'options.*' => ['required', 'string', 'max:100'],
            'track_inventory' => ['sometimes', 'boolean'],
            'stock_quantity' => ['prohibited'],
            'is_active' => ['sometimes', 'boolean'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}
