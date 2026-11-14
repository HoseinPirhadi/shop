<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string|null $store_name
 * @property string|null $currency_code
 */
class StoreSetting extends Model
{
    public $incrementing = false;

    /** @var list<string> */
    protected $fillable = [
        'store_name',
        'currency_code',
    ];

    public static function current(): self
    {
        return static::query()->findOrFail(1);
    }

    public function hasConfiguredCurrency(): bool
    {
        return $this->currency_code !== null;
    }
}
