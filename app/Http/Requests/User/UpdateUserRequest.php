<?php

namespace App\Http\Requests\User;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Kullanıcı güncelleme formu doğrulama sınıfı.
 */
class UpdateUserRequest extends FormRequest
{
    /**
     * İsteğin yetkili olup olmadığını belirler.
     */
    public function authorize(): bool
    {
        /** @var User|null $targetUser */
        $targetUser = $this->route('user');

        return $targetUser !== null && ($this->user()?->can('update', $targetUser) ?? false);
    }

    /**
     * Doğrulama kuralları.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User $targetUser */
        $targetUser = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($targetUser->id)],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^(\+90|0)?[0-9]{10}$/'],
            'password' => ['nullable', 'string', Password::min(8)->mixedCase()->numbers()],
            'password_confirmation' => ['nullable', 'required_with:password', 'same:password'],
            'status' => ['required', Rule::enum(UserStatus::class)],
            'roles' => ['nullable', 'array'],
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
            'email.unique' => 'Bu e-posta adresi başka bir kullanıcı tarafından kullanılıyor.',
            'phone.regex' => 'Geçerli bir telefon numarası giriniz.',
            'password_confirmation.same' => 'Şifre tekrarı eşleşmiyor.',
        ];
    }
}
