<?php

namespace App\Http\Requests\Sms;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Toplu SMS gönderim formu doğrulama sınıfı.
 */
class SendBulkSmsRequest extends FormRequest
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
            'recipients' => ['required', 'string'],
            'message' => ['required', 'string', 'min:1', 'max:918'],
            'sender_id' => ['nullable', 'string', 'max:20'],
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
            'recipients.required' => 'Telefon numaraları zorunludur.',
            'message.required' => 'Mesaj metni zorunludur.',
            'message.max' => 'Mesaj en fazla 918 karakter olabilir.',
        ];
    }
}
