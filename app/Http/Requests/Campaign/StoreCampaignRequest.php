<?php

namespace App\Http\Requests\Campaign;

use App\Models\SmsCampaign;
use Illuminate\Foundation\Http\FormRequest;

class StoreCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', SmsCampaign::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:1000'],
            'sender_id' => ['nullable', 'string', 'max:11'],
            'contact_ids' => ['nullable', 'array'],
            'contact_ids.*' => ['integer', 'exists:contacts,id'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
