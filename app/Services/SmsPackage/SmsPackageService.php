<?php

namespace App\Services\SmsPackage;

use App\Exceptions\BusinessException;
use App\Models\SmsPackage;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class SmsPackageService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): SmsPackage
    {
        return SmsPackage::create([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name']),
            'description' => $data['description'] ?? null,
            'sms_amount' => (int) $data['sms_amount'],
            'price' => isset($data['price']) && $data['price'] !== '' ? $data['price'] : null,
            'is_active' => (bool) ($data['is_active'] ?? true),
            'is_public' => (bool) ($data['is_public'] ?? false),
            'sort_order' => (int) ($data['sort_order'] ?? 100),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(SmsPackage $package, array $data): SmsPackage
    {
        $package->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'sms_amount' => (int) $data['sms_amount'],
            'price' => isset($data['price']) && $data['price'] !== '' ? $data['price'] : null,
            'is_active' => (bool) ($data['is_active'] ?? false),
            'is_public' => (bool) ($data['is_public'] ?? false),
            'sort_order' => (int) ($data['sort_order'] ?? 100),
        ]);

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
        $query = SmsPackage::query()->orderBy('sort_order')->orderBy('name');

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
        return SmsPackage::query()
            ->where('is_active', true)
            ->where('is_public', true)
            ->orderBy('sort_order')
            ->orderBy('sms_amount')
            ->get();
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
