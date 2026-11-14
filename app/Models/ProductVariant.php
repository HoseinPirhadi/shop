<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $product_id
 * @property string $name
 * @property string|null $sku
 * @property array<string, string>|null $options
 * @property int $stock_quantity
 * @property bool $track_inventory
 * @property bool $is_active
 * @property bool $is_default
 */
class ProductVariant extends Model
{
    protected static function booted(): void
    {
        static::creating(function (ProductVariant $variant): void {
            $variant->stock_quantity ??= 0;
            $variant->track_inventory ??= true;
            $variant->is_active ??= true;
            $variant->is_default ??= false;
        });
    }

    /** @var list<string> */
    protected $fillable = [
        'name',
        'sku',
        'options',
        'stock_quantity',
        'track_inventory',
        'is_active',
        'is_default',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'options' => 'array',
            'stock_quantity' => 'integer',
            'track_inventory' => 'boolean',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return HasMany<InventoryMovement, $this>
     */
    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }
}
