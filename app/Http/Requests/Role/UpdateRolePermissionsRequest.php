<?php

namespace App\Http\Requests\Role;

use App\Enums\RoleName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRolePermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('roles.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ];
    }

    protected function prepareForValidation(): void
    {
        /** @var \Spatie\Permission\Models\Role|null $role */
        $role = $this->route('role');

        if ($role?->name === RoleName::SuperAdmin->value) {
            $this->merge([
                'permissions' => \Spatie\Permission\Models\Permission::pluck('name')->all(),
            ]);
        }
    }
}
