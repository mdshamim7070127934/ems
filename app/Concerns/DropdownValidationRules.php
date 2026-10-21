<?php

namespace App\Concerns;

use App\Enums\Master\Status;
use Illuminate\Validation\Rule;

trait DropdownValidationRules
{
    /**
     * Get the validation rules used to validate dropdowns.
     *
     * @return array<string, mixed>
     */
    protected function dropdownRules(?int $optionTypeId = null): array
    {
        return [
            'module_id' => ['required', 'integer', Rule::exists('mst_module', 'module_id')],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('mst_dropdown', 'name')
                    ->where('module_id', $this->input('module_id'))
                    ->ignore($optionTypeId, 'option_type_id'),
            ],
            'status' => ['required', Rule::enum(Status::class)],
        ];
    }
}
