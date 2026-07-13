<?php

namespace App\Http\Requests\SmsPackage;

use App\Models\SmsPackage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSmsPackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $package = $this->route('package');

        return $package instanceof SmsPackage && ($this->user()?->can('update', $package) ?? false);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'badge' => ['nullable', 'string', 'max:50'],
            'features' => ['nullable', 'string', 'max:5000'],
            'theme' => ['required', Rule::in(['indigo', 'emerald', 'cyan', 'amber', 'rose'])],
            'sms_amount' => ['required', 'integer', 'min:1', 'max:10000000'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'is_public' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'is_public' => $this->boolean('is_public'),
            'is_featured' => $this->boolean('is_featured'),
        ]);
    }
}
