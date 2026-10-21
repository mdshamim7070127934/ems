<?php

namespace App\Http\Requests\Master;

use App\Concerns\CityValidationRules;
use App\Models\Master\City;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCityRequest extends FormRequest
{
    use CityValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var City $city */
        $city = $this->route('city');

        return $this->cityRules($city->id);
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'country_id' => 'country',
            'state_id' => 'state',
        ];
    }
}
