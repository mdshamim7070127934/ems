<?php

namespace App\Concerns;

use App\Enums\Master\Status;
use Illuminate\Validation\Rule;

trait EmailTypeValidationRules
{
    /**
     * Normalize email type fields before validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge([
                'code' => strtoupper(trim((string) $this->input('code'))),
            ]);
        }

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
     * Get the validation rules used to validate email types.
     *
     * @return array<string, mixed>
     */
    protected function emailTypeRules(?int $id = null): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z][A-Z0-9_]*$/',
                Rule::unique('mst_email_type', 'code')->ignore($id),
            ],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'status' => ['required', Rule::enum(Status::class)],
        ];
    }
}
