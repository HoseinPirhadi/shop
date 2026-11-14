<?php

namespace App\Http\Resources;

use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProductVariant
 */
class AdminProductVariantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'options' => $this->options,
            'stock_quantity' => $this->stock_quantity,
            'track_inventory' => $this->track_inventory,
            'is_available' => ! $this->track_inventory || $this->stock_quantity > 0,
            'is_active' => $this->is_active,
            'is_default' => $this->is_default,
        ];
    }
}
