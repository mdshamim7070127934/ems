<?php

namespace App\Http\Requests\Master;

use App\Concerns\EmailTypeValidationRules;
use App\Models\Master\EmailType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEmailTypeRequest extends FormRequest
{
    use EmailTypeValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var EmailType $emailType */
        $emailType = $this->route('email_type');

        return $this->emailTypeRules($emailType->id);
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
