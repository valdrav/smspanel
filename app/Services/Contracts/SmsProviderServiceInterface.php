<?php

namespace App\Services\Contracts;

use App\DTOs\SmsProvider\CreateSmsProviderData;
use App\DTOs\SmsProvider\UpdateSmsProviderData;
use App\Models\SmsProvider;
use App\Sms\DTOs\SmsBalanceResult;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SmsProviderServiceInterface
{
    /**
     * @return LengthAwarePaginator<int, SmsProvider>
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function create(CreateSmsProviderData $data): SmsProvider;

    public function update(SmsProvider $provider, UpdateSmsProviderData $data): SmsProvider;

    public function delete(SmsProvider $provider): void;

    public function testBalance(SmsProvider $provider): SmsBalanceResult;
}
