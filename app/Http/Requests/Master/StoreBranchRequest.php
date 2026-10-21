<?php

namespace App\Http\Requests\Master;

use App\Concerns\BranchValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBranchRequest extends FormRequest
{
    use BranchValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->branchRules();
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'branch_name' => 'branch name',
            'branch_code' => 'branch code',
            'branch_manager' => 'branch manager',
            'branch_country_code' => 'branch country code',
            'branch_fbcountry_code' => 'branch FB country code',
            'country_id' => 'country',
            'region_id' => 'region',
            'group_id' => 'group',
            'owner_id' => 'owner',
            'man_power' => 'man power',
            'retail_man_power' => 'retail man power',
            'geofencing_range' => 'geofencing range',
        ];
    }
}
