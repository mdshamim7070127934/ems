<?php

namespace App\Http\Requests\Master;

use App\Concerns\EmailAccountValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreEmailAccountRequest extends FormRequest
{
    use EmailAccountValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->emailAccountRules();
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'account name',
            'from_name' => 'from name',
            'from_email' => 'from email',
            'smtp_host' => 'SMTP host',
            'smtp_port' => 'SMTP port',
            'smtp_encryption' => 'SMTP encryption',
            'is_default' => 'default account',
            'email_type_ids' => 'email types',
            'email_type_ids.*' => 'email type',
        ];
    }
}
