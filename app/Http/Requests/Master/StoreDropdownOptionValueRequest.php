<?php

namespace App\Http\Requests\Master;

use App\Concerns\DropdownOptionValueValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreDropdownOptionValueRequest extends FormRequest
{
    use DropdownOptionValueValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->dropdownOptionValueRules();
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'module_id' => 'module',
            'option_type_id' => 'dropdown',
            'parent_id' => 'parent',
            'option_value' => 'option value',
            'user_access_id' => 'user access',
            'master_type' => 'master type',
        ];
    }
}
