<?php

namespace App\Concerns;

use App\Enums\Master\CountryStatus;
use Illuminate\Validation\Rule;

trait CountryValidationRules
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $timezoneId = $this->input('timezone_id');

        $this->merge([
            'timezone_id' => $timezoneId === '' || $timezoneId === 'none' ? null : $timezoneId,
        ]);
    }

    /**
     * Get the validation rules used to validate countries.
     *
     * @return array<string, mixed>
     */
    protected function countryRules(?int $id = null): array
    {
        return [
            'sortname' => ['required', 'string', 'max:3', Rule::unique('mst_country', 'sortname')->ignore($id)],
            'name' => ['required', 'string', 'max:150', Rule::unique('mst_country', 'name')->ignore($id)],
            'currency' => ['required', 'string', 'max:100'],
            'phonecode' => ['required', 'integer'],
            'timezone_id' => ['nullable', 'integer', Rule::exists('mst_timezone', 'id')],
            'status' => ['required', Rule::enum(CountryStatus::class)],
        ];
    }
}
