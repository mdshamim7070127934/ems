<?php

namespace App\Concerns;

use App\Enums\Master\Status;
use Illuminate\Validation\Rule;

trait GroupValidationRules
{
    /**
     * Get the validation rules used to validate groups.
     *
     * @return array<string, mixed>
     */
    protected function groupRules(?int $id = null): array
    {
        return [
            'group_name' => ['required', 'string', 'max:250', Rule::unique('mst_group', 'group_name')->ignore($id)],
            'description' => ['required', 'string'],
            'owner_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'status' => ['required', Rule::enum(Status::class)],
        ];
    }
}
