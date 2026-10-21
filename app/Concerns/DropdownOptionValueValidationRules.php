<?php

namespace App\Concerns;

use App\Enums\Master\Status;
use App\Models\Master\DropdownOptionValue;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

trait DropdownOptionValueValidationRules
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'parent_id' => $this->input('parent_id') === '' || $this->input('parent_id') === null
                ? 0
                : $this->input('parent_id'),
            'position' => $this->input('position') === '' ? null : $this->input('position'),
            'master_type' => $this->input('master_type') === '' ? null : $this->input('master_type'),
            'user_access_id' => $this->input('user_access_id') === '' ? null : $this->input('user_access_id'),
        ]);
    }

    /**
     * Get the validation rules used to validate dropdown option values.
     *
     * @return array<string, mixed>
     */
    protected function dropdownOptionValueRules(?int $id = null): array
    {
        return [
            'module_id' => ['required', 'integer', Rule::exists('mst_module', 'module_id')],
            'option_type_id' => [
                'required',
                'integer',
                Rule::exists('mst_dropdown', 'option_type_id')
                    ->where('module_id', $this->input('module_id')),
            ],
            'parent_id' => ['required', 'integer', 'min:0'],
            'option_value' => [
                'required',
                'string',
                'max:100',
                Rule::unique('mst_dropdown_option_values', 'option_value')
                    ->where('option_type_id', $this->input('option_type_id'))
                    ->ignore($id),
            ],
            'position' => ['nullable', 'integer', 'min:0'],
            'user_access_id' => ['nullable', 'string'],
            'master_type' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::enum(Status::class)],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $parentId = $this->integer('parent_id');

                if ($parentId === 0) {
                    return;
                }

                /** @var DropdownOptionValue|null $current */
                $current = $this->route('dropdown_option_value');

                if ($current !== null && $parentId === $current->id) {
                    $validator->errors()->add('parent_id', 'The parent cannot be the same record.');

                    return;
                }

                $parentExists = DropdownOptionValue::query()
                    ->whereKey($parentId)
                    ->where('option_type_id', $this->integer('option_type_id'))
                    ->exists();

                if (! $parentExists) {
                    $validator->errors()->add('parent_id', 'The selected parent is invalid.');
                }
            },
        ];
    }
}
