<?php

namespace App\Http\Requests\SmsProvider;

use App\Enums\SmsProviderDriver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSmsProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\SmsProvider::class) ?? false;
    }

    public function rules(): array
    {
        return array_merge([
            'code' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:sms_providers,code'],
            'name' => ['required', 'string', 'max:255'],
            'driver' => ['required', Rule::enum(SmsProviderDriver::class)],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:9999'],
        ], $this->driverConfigRules());
    }

    /**
     * @return array<string, mixed>
     */
    protected function driverConfigRules(): array
    {
        return match ($this->input('driver')) {
            SmsProviderDriver::Netgsm->value => [
                'config.usercode' => ['required', 'string', 'max:100'],
                'config.password' => ['required', 'string', 'max:100'],
                'config.msgheader' => ['nullable', 'string', 'max:11'],
            ],
            SmsProviderDriver::IletiMerkezi->value => [
                'config.api_key' => ['required', 'string', 'max:100'],
                'config.secret' => ['required', 'string', 'max:100'],
                'config.sender' => ['nullable', 'string', 'max:11'],
            ],
            SmsProviderDriver::EasySendSms->value => [
                'config.api_key' => ['required', 'string', 'max:500'],
                'config.sender_id' => ['required', 'string', 'max:15'],
                'config.base_url' => ['nullable', 'url', 'max:255'],
            ], // alfanumerik max 11 / sayısal max 15 (sağlayıcıda da doğrulanır)
            default => [],
        };
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active', true),
            'is_default' => $this->boolean('is_default', false),
        ]);
    }
}
