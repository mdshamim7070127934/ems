<?php

namespace App\Http\Requests\Master;

use App\Concerns\CountryValidationRules;
use App\Models\Master\Country;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCountryRequest extends FormRequest
{
    use CountryValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Country $country */
        $country = $this->route('country');

        return $this->countryRules($country->id);
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'sortname' => 'sort name',
            'phonecode' => 'phone code',
            'timezone_id' => 'timezone',
        ];
    }
}
