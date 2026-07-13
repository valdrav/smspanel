<?php

namespace App\Http\Requests\Sms;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Tekil SMS gönderim formu doğrulama sınıfı.
 */
class SendSmsRequest extends FormRequest
{
    /**
     * İsteğin yetkili olup olmadığını belirler.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('sms.send') ?? false;
    }

    /**
     * Doğrulama kuralları.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'recipient' => ['required', 'string', 'max:20'],
            'message' => ['required', 'string', 'min:1', 'max:918'],
            'sender_id' => ['nullable', 'string', 'max:11', 'alpha_num'],
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
            'recipient.required' => 'Telefon numarası zorunludur.',
            'message.required' => 'Mesaj metni zorunludur.',
            'message.max' => 'Mesaj en fazla 918 karakter olabilir.',
            'sender_id.alpha_num' => 'Gönderici başlığı yalnızca harf ve rakam içerebilir.',
        ];
    }
}
