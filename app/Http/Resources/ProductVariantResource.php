<?php

namespace App\\Http\\Resources;

use App\\Models\\ProductVariant;
use Illuminate\\Http\\Request;
use Illuminate\\Http\\Resources\\Json\\JsonResource;

/**
 * @mixin ProductVariant
 */
class ProductVariantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $available = ! $this->track_inventory || $this->stock_quantity > 0;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'options' => $this->options,
            'is_available' => $available && $this->is_active,
            'sku' => $this->when($request->user()?->isAdmin() ?? false, $this->sku),
            'stock_quantity' => $this->when($request->user()?->isAdmin() ?? false, $this->stock_quantity),
            'track_inventory' => $this->when($request->user()?->isAdmin() ?? false, $this->track_inventory),
            'is_active' => $this->when($request->user()?->isAdmin() ?? false, $this->is_active),
            'is_default' => $this->when($request->user()?->isAdmin() ?? false, $this->is_default),
        ];
    }
}
