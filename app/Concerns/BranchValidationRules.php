<?php

namespace App\Concerns;

use App\Enums\Master\Status;
use Illuminate\Validation\Rule;

trait BranchValidationRules
{
    /**
     * Normalize optional branch fields before validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'geofencing' => $this->boolean('geofencing'),
            'owner_id' => ! $this->filled('owner_id') || $this->input('owner_id') === 'none'
                ? null
                : $this->input('owner_id'),
            'geofencing_range' => $this->filled('geofencing_range') ? $this->input('geofencing_range') : null,
            'branch_country_code' => $this->filled('branch_country_code') ? $this->input('branch_country_code') : null,
            'man_power' => $this->filled('man_power') ? $this->input('man_power') : null,
            'retail_man_power' => $this->filled('retail_man_power') ? $this->input('retail_man_power') : null,
        ]);
    }

    /**
     * Get the validation rules used to validate branches.
     *
     * @return array<string, mixed>
     */
    protected function branchRules(?int $id = null): array
    {
        return [
            'branch_name' => ['required', 'string', 'max:100'],
            'geofencing' => ['required', 'boolean'],
            'geofencing_range' => ['nullable', 'required_if:geofencing,true', 'string', 'max:50'],
            'branch_country_code' => ['nullable', 'string', 'max:255'],
            'branch_fbcountry_code' => ['required', 'string', 'max:100'],
            'branch_manager' => ['required', 'integer', Rule::exists('users', 'id')],
            'country_id' => ['required', 'integer', Rule::exists('mst_country', 'id')],
            'region_id' => [
                'required',
                'integer',
                Rule::exists('mst_region', 'id')
                    ->where('country_id', $this->input('country_id')),
            ],
            'group_id' => ['required', 'integer', Rule::exists('mst_group', 'id')],
            'branch_code' => ['required', 'string', 'max:50', Rule::unique('mst_branch', 'branch_code')->ignore($id)],
            'owner_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'man_power' => ['nullable', 'integer', 'min:0'],
            'retail_man_power' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', Rule::enum(Status::class)],
        ];
    }
}
