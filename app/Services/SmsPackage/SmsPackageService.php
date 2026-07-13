<?php

namespace App\Services\SmsPackage;

use App\Exceptions\BusinessException;
use App\Models\SmsPackage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class SmsPackageService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): SmsPackage
    {
        return SmsPackage::create($this->payload($data) + [
            'slug' => $this->uniqueSlug($data['name']),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(SmsPackage $package, array $data): SmsPackage
    {
        $package->update($this->payload($data));

        return $package->fresh();
    }

    public function delete(SmsPackage $package): void
    {
        if ($package->orders()->where('status', 'pending')->exists()) {
            throw new BusinessException('Bekleyen siparişi olan paket silinemez.');
        }
        $package->delete();
    }

    public function listAdmin(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = SmsPackage::query();

        if (SmsPackage::hasPackageEnhancements()) {
            $query->orderByDesc('is_featured');
        }

        $query->orderBy('sort_order')->orderBy('name');

        if (isset($filters['is_public']) && $filters['is_public'] !== '') {
            $query->where('is_public', filter_var($filters['is_public'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, SmsPackage>
     */
    public function listPublic(): \Illuminate\Database\Eloquent\Collection
    {
        $query = SmsPackage::query()
            ->where('is_active', true)
            ->where('is_public', true);

        if (SmsPackage::hasPackageEnhancements()) {
            $query->orderByDesc('is_featured');
        }

        return $query
            ->orderBy('sort_order')
            ->orderBy('sms_amount')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payload(array $data): array
    {
        $payload = [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'sms_amount' => (int) $data['sms_amount'],
            'price' => isset($data['price']) && $data['price'] !== '' ? $data['price'] : null,
            'is_active' => (bool) ($data['is_active'] ?? true),
            'is_public' => (bool) ($data['is_public'] ?? false),
            'sort_order' => (int) ($data['sort_order'] ?? 100),
        ];

        if (SmsPackage::hasPackageEnhancements()) {
            $payload['badge'] = $data['badge'] ?? null;
            $payload['features'] = $data['features'] ?? [];
            $payload['theme'] = $data['theme'] ?? 'indigo';
            $payload['is_featured'] = (bool) ($data['is_featured'] ?? false);
        }

        return $payload;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while (SmsPackage::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
