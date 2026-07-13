<?php

namespace App\Http\Requests\UserSenderNumber;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserSenderNumberRequest extends FormRequest
{
    public function authorize(): bool
    {
        $senderNumber = $this->route('user_sender_number');

        return $senderNumber && ($this->user()?->can('update', $senderNumber) ?? false);
    }

    public function rules(): array
    {
        return [
            'sender_id' => ['required', 'string', 'max:11', 'alpha_num'],
            'label' => ['nullable', 'string', 'max:100'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'sender_id' => strtoupper((string) $this->input('sender_id')),
            'is_default' => $this->boolean('is_default'),
            'is_active' => $this->boolean('is_active', true),
        ]);
    }
}
