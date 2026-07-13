<?php

namespace App\Http\Requests\UserSenderNumber;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserSenderNumberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\UserSenderNumber::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
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

    public function messages(): array
    {
        return [
            'user_id.required' => 'Kullanıcı seçimi zorunludur.',
            'sender_id.required' => 'Gönderici numarası zorunludur.',
            'sender_id.alpha_num' => 'Gönderici numarası yalnızca harf ve rakam içerebilir.',
        ];
    }
}
