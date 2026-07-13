<?php

namespace App\Http\Requests\SmsPackage;

use App\Models\SmsPackage;
use Illuminate\Foundation\Http\FormRequest;

class StoreSmsPackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', SmsPackage::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'sms_amount' => ['required', 'integer', 'min:1', 'max:10000000'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'is_public' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active', true),
            'is_public' => $this->boolean('is_public', false),
        ]);
    }
}
