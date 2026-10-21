<?php

namespace App\Concerns;

use App\Enums\Master\Status;
use App\Models\Master\Timezone;
use Illuminate\Validation\Rule;

trait TimezoneValidationRules
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $name = $this->input('name');

        if (! is_string($name) || ! in_array($name, Timezone::identifiers(), true)) {
            return;
        }

        $attributes = Timezone::attributesFor($name);
        $displayName = $this->input('display_name');

        $this->merge([
            'code' => $attributes['code'],
            'utc_offset' => $attributes['utc_offset'],
            'display_name' => is_string($displayName) && trim($displayName) !== ''
                ? $displayName
                : $attributes['display_name'],
        ]);
    }

    /**
     * Get the validation rules used to validate timezones.
     *
     * @return array<string, mixed>
     */
    protected function timezoneRules(?int $id = null): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                'timezone',
                Rule::unique('mst_timezone', 'name')->ignore($id),
            ],
            'code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('mst_timezone', 'code')->ignore($id),
            ],
            'display_name' => ['required', 'string', 'max:150'],
            'utc_offset' => ['required', 'string', 'regex:/^[+-](?:[01]\d|2[0-3]):[0-5]\d$/'],
            'status' => ['required', Rule::enum(Status::class)],
        ];
    }
}
