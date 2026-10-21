<?php

namespace App\Concerns;

use App\Enums\Master\Status;
use Illuminate\Validation\Rule;

trait EditorSettingValidationRules
{
    /**
     * Normalize editor setting fields before validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge([
                'name' => trim((string) $this->input('name')),
            ]);
        }

        $this->merge([
            'description' => $this->filled('description') ? trim((string) $this->input('description')) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function editorSettingRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'status' => ['required', Rule::enum(Status::class)],
        ];
    }
}
