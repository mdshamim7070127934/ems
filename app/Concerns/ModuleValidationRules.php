<?php

namespace App\Concerns;

use App\Enums\Master\Status;
use Illuminate\Validation\Rule;

trait ModuleValidationRules
{
    /**
     * Get the validation rules used to validate modules.
     *
     * @return array<string, mixed>
     */
    protected function moduleRules(?int $moduleId = null): array
    {
        return [
            'module_name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('mst_module', 'module_name')->ignore($moduleId, 'module_id'),
            ],
            'status' => ['required', Rule::enum(Status::class)],
        ];
    }
}
