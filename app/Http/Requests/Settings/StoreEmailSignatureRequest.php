<?php

namespace App\Http\Requests\Settings;

use App\Concerns\EmailSignatureValidationRules;
use App\Models\Mailbox\EmailSignature;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreEmailSignatureRequest extends FormRequest
{
    use EmailSignatureValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->can('create', EmailSignature::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->emailSignatureRules();
    }
}
