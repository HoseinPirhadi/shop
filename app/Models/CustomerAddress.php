<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $label
 * @property string $recipient_name
 * @property string $phone
 * @property string $country_code
 * @property string|null $state
 * @property string $city
 * @property string $address_line1
 * @property string|null $address_line2
 * @property string|null $postal_code
 * @property bool $is_default
 */
class CustomerAddress extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'label',
        'recipient_name',
        'phone',
        'country_code',
        'state',
        'city',
        'address_line1',
        'address_line2',
        'postal_code',
        'is_default',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
