<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmsPackage extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name', 'slug', 'description', 'badge', 'features', 'theme',
        'sms_amount', 'price', 'is_active', 'is_public', 'is_featured', 'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sms_amount' => 'integer',
            'price' => 'decimal:2',
            'features' => 'array',
            'is_active' => 'boolean',
            'is_public' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return HasMany<PackageOrder, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(PackageOrder::class);
    }

    /**
     * @return list<string>
     */
    public function featureList(): array
    {
        $features = $this->features;

        if (! is_array($features)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn ($item) => is_string($item) ? trim($item) : '',
            $features
        )));
    }

    public function pricePerSms(): ?float
    {
        if ($this->price === null || $this->sms_amount <= 0) {
            return null;
        }

        return round((float) $this->price / $this->sms_amount, 4);
    }

    public function themeClass(): string
    {
        return match ($this->theme) {
            'emerald' => 'pkg-theme-emerald',
            'cyan' => 'pkg-theme-cyan',
            'amber' => 'pkg-theme-amber',
            'rose' => 'pkg-theme-rose',
            default => 'pkg-theme-indigo',
        };
    }
}
