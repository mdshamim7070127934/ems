<?php

namespace App\Http\Requests\Master;

use App\Concerns\CountryValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCountryRequest extends FormRequest
{
    use CountryValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->countryRules();
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
