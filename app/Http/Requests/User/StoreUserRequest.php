<?php

namespace App\Http\Requests\User;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Kullanıcı oluşturma formu doğrulama sınıfı.
 */
class StoreUserRequest extends FormRequest
{
    /**
     * İsteğin yetkili olup olmadığını belirler.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\User::class) ?? false;
    }

    /**
     * Doğrulama kuralları.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^(\+90|0)?[0-9]{10}$/'],
            'password' => ['required', 'string', Password::min(8)->mixedCase()->numbers()],
            'password_confirmation' => ['required', 'same:password'],
            'status' => ['required', Rule::enum(UserStatus::class)],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', Rule::in(array_column(RoleName::cases(), 'value'))],
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
            'name.required' => 'Ad soyad zorunludur.',
            'email.required' => 'E-posta adresi zorunludur.',
            'email.unique' => 'Bu e-posta adresi zaten kayıtlı.',
            'phone.regex' => 'Geçerli bir telefon numarası giriniz.',
            'password.required' => 'Şifre zorunludur.',
            'password_confirmation.same' => 'Şifre tekrarı eşleşmiyor.',
            'roles.required' => 'En az bir rol seçilmelidir.',
        ];
    }
}
