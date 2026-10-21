<?php

namespace App\Http\Requests\Master;

use App\Concerns\RoleValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends FormRequest
{
    use RoleValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->roleRules();
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'role_name' => 'role name',
            'display_name' => 'display name',
            'is_active' => 'status',
            'menu_ids' => 'menu permissions',
            'menu_ids.*' => 'menu',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'role_name.regex' => 'The role name must start with a letter and contain only lowercase letters, numbers, and underscores.',
        ];
    }
}
