<?php

namespace App\Models;

use App\Enums\PackageOrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsPackage extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name', 'slug', 'description', 'sms_amount', 'price',
        'is_active', 'is_public', 'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sms_amount' => 'integer',
            'price' => 'decimal:2',
            'is_active' => 'boolean',
            'is_public' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return HasMany<PackageOrder, $this>
     */
    public function orders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PackageOrder::class);
    }
}
