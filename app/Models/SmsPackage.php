<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

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
     * JSON özellik listesi — bozuk kayıtta sayfa patlamasın.
     *
     * @return Attribute<list<string>, list<string>|string|null>
     */
    protected function features(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value): array {
                if ($value === null || $value === '') {
                    return [];
                }

                if (is_array($value)) {
                    return $value;
                }

                if (! is_string($value)) {
                    return [];
                }

                $decoded = json_decode($value, true);

                return is_array($decoded) ? $decoded : [];
            },
            set: function (mixed $value): ?string {
                if ($value === null || $value === '') {
                    return null;
                }

                if (is_string($value)) {
                    $lines = preg_split('/\r\n|\r|\n/', $value) ?: [];
                    $value = array_values(array_filter(array_map('trim', $lines)));
                }

                if (! is_array($value)) {
                    return null;
                }

                $clean = array_values(array_filter(array_map(
                    static fn ($item) => is_string($item) ? trim($item) : '',
                    $value
                )));

                return $clean === [] ? null : json_encode($clean, JSON_UNESCAPED_UNICODE);
            },
        );
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
        if ($this->price === null || (int) $this->sms_amount <= 0) {
            return null;
        }

        return round((float) $this->price / (int) $this->sms_amount, 4);
    }

    public function themeClass(): string
    {
        return match ($this->theme ?: 'indigo') {
            'emerald' => 'pkg-theme-emerald',
            'cyan' => 'pkg-theme-cyan',
            'amber' => 'pkg-theme-amber',
            'rose' => 'pkg-theme-rose',
            default => 'pkg-theme-indigo',
        };
    }

    public function isFeaturedSafe(): bool
    {
        if (! $this->hasPackageEnhancements()) {
            return false;
        }

        return (bool) $this->is_featured;
    }

    public static function hasPackageEnhancements(): bool
    {
        static $cached = null;

        if ($cached === null) {
            $cached = Schema::hasColumn('sms_packages', 'is_featured');
        }

        return $cached;
    }
}
