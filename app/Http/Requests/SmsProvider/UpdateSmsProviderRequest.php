<?php

namespace App\Http\Requests\SmsProvider;

use App\Enums\SmsProviderDriver;
use App\Models\SmsProvider;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSmsProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var SmsProvider|null $provider */
        $provider = $this->route('sms_provider');

        return $provider !== null && ($this->user()?->can('update', $provider) ?? false);
    }

    public function rules(): array
    {
        return array_merge([
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
            default => [],
        };
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'is_default' => $this->boolean('is_default'),
        ]);
    }
}
