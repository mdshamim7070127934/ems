<?php

namespace App\Http\Requests\Master;

use App\Concerns\DropdownValidationRules;
use App\Models\Master\Dropdown;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDropdownRequest extends FormRequest
{
    use DropdownValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Dropdown $dropdown */
        $dropdown = $this->route('dropdown');

        return $this->dropdownRules($dropdown->option_type_id);
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
        ];
    }
}
