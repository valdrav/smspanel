<?php

namespace App\Http\Requests\Organization;

use App\Enums\OrganizationStatus;
use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Organization|null $organization */
        $organization = $this->route('organization');

        return $organization !== null && ($this->user()?->can('update', $organization) ?? false);
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
        ];
    }
}
