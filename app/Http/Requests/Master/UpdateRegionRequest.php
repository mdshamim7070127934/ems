<?php

namespace App\Http\Requests\Master;

use App\Concerns\RegionValidationRules;
use App\Models\Master\Region;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRegionRequest extends FormRequest
{
    use RegionValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Region $region */
        $region = $this->route('region');

        return $this->regionRules($region->id);
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'region_name' => 'region name',
            'country_id' => 'country',
            'owner_id' => 'owner',
        ];
    }
}
