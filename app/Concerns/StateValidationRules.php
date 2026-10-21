<?php

namespace App\Concerns;

use App\Enums\Master\Status;
use Illuminate\Validation\Rule;

trait StateValidationRules
{
    /**
     * Get the validation rules used to validate states.
     *
     * @return array<string, mixed>
     */
    protected function stateRules(?int $id = null): array
    {
        return [
            'country_id' => ['required', 'integer', Rule::exists('mst_country', 'id')],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('mst_state', 'name')
                    ->where('country_id', $this->input('country_id'))
                    ->ignore($id),
            ],
            'status' => ['required', Rule::enum(Status::class)],
        ];
    }
}
