<?php

namespace App\Concerns;

use App\Enums\Master\Status;
use Illuminate\Validation\Rule;

trait CityValidationRules
{
    /**
     * Get the validation rules used to validate cities.
     *
     * @return array<string, mixed>
     */
    protected function cityRules(?int $id = null): array
    {
        return [
            'country_id' => ['required', 'integer', Rule::exists('mst_country', 'id')],
            'state_id' => [
                'required',
                'integer',
                Rule::exists('mst_state', 'id')
                    ->where('country_id', $this->input('country_id')),
            ],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('mst_city', 'name')
                    ->where('state_id', $this->input('state_id'))
                    ->ignore($id),
            ],
            'status' => ['required', Rule::enum(Status::class)],
        ];
    }
}
