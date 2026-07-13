<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Giriş formu doğrulama sınıfı.
 */
class LoginRequest extends FormRequest
{
    /**
     * İsteğin yetkili olup olmadığını belirler.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Doğrulama kuralları.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Türkçe hata mesajları.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'E-posta adresi zorunludur.',
            'email.email' => 'Geçerli bir e-posta adresi giriniz.',
            'password.required' => 'Şifre zorunludur.',
            'password.min' => 'Şifre en az 8 karakter olmalıdır.',
        ];
    }
}
