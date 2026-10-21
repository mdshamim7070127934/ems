<?php

namespace App\Http\Requests\Master;

use App\Concerns\EmailTypeValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreEmailTypeRequest extends FormRequest
{
    use EmailTypeValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->emailTypeRules();
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'sort_order' => 'sort order',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.regex' => 'The code must start with a letter and contain only uppercase letters, numbers, and underscores.',
        ];
    }
}
