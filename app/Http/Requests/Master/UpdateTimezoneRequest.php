<?php

namespace App\Http\Requests\Master;

use App\Concerns\TimezoneValidationRules;
use App\Models\Master\Timezone;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTimezoneRequest extends FormRequest
{
    use TimezoneValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Timezone $timezone */
        $timezone = $this->route('timezone');

        return $this->timezoneRules($timezone->id);
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
