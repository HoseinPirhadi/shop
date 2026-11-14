<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $product_id
 * @property int|null $product_variant_id
 * @property int|null $created_by
 * @property int $quantity_delta
 * @property int $quantity_before
 * @property int $quantity_after
 * @property string $reason
 */
class InventoryMovement extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'product_id',
        'product_variant_id',
        'created_by',
        'quantity_delta',
        'quantity_before',
        'quantity_after',
        'reason',
    ];

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
