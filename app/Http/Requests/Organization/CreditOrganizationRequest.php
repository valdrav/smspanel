<?php

namespace App\Http\Requests\Organization;

use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;

class CreditOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Organization|null $organization */
        $organization = $this->route('organization');

        return $organization !== null && ($this->user()?->can('credit', $organization) ?? false);
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:1', 'max:9999999'],
            'description' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'SMS adedi zorunludur.',
            'amount.min' => 'En az 1 SMS yüklenmelidir.',
            'description.required' => 'Açıklama zorunludur.',
        ];
    }
}
