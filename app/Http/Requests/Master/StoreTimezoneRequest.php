<?php

namespace App\Http\Requests\Master;

use App\Concerns\TimezoneValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTimezoneRequest extends FormRequest
{
    use TimezoneValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->timezoneRules();
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'timezone name',
            'display_name' => 'display name',
            'utc_offset' => 'UTC offset',
        ];
    }
}
