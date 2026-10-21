<?php

namespace App\Concerns;

use Illuminate\Validation\Rule;

trait RoleValidationRules
{
    /**
     * Normalize role fields before validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('role_name')) {
            $this->merge([
                'role_name' => strtolower(trim((string) $this->input('role_name'))),
            ]);
        }

        if ($this->has('display_name')) {
            $this->merge([
                'display_name' => trim((string) $this->input('display_name')),
            ]);
        }

        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'menu_ids' => array_values(array_unique(array_map(
                intval(...),
                array_filter((array) $this->input('menu_ids', [])),
            ))),
        ]);
    }

    /**
     * Get the validation rules used to validate roles.
     *
     * @return array<string, mixed>
     */
    protected function roleRules(?int $id = null): array
    {
        return [
            'role_name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('roles', 'role_name')->ignore($id),
            ],
            'display_name' => ['required', 'string', 'max:100'],
            'is_active' => ['required', 'boolean'],
            'menu_ids' => ['present', 'array'],
            'menu_ids.*' => ['integer', Rule::exists('mst_menu', 'id')],
        ];
    }
}
