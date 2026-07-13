<?php

namespace App\Http\Requests\Package;

use App\Models\PackageOrder;
use App\Models\SmsPackage;
use Illuminate\Foundation\Http\FormRequest;

class PurchasePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', PackageOrder::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'user_note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
