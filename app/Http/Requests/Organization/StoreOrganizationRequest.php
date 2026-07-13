<?php

namespace App\Http\Requests\Organization;

use App\Enums\OrganizationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Organization::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'tax_number' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::enum(OrganizationStatus::class)],
            'sms_sender_id' => ['nullable', 'string', 'max:11', 'alpha_num'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'initial_balance' => ['nullable', 'integer', 'min:0', 'max:9999999'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Organizasyon adı zorunludur.',
        ];
    }
}
