<?php

namespace App\Http\Requests\Settings;

use App\Concerns\EmailSignatureValidationRules;
use App\Models\Mailbox\EmailSignature;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEmailSignatureRequest extends FormRequest
{
    use EmailSignatureValidationRules;

    public function authorize(): bool
    {
        $signature = $this->route('email_signature');

        return $signature instanceof EmailSignature
            && ($this->user()?->can('update', $signature) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $signature = $this->route('email_signature');
        $ignoreId = $signature instanceof EmailSignature ? $signature->id : null;

        return $this->emailSignatureRules($ignoreId);
    }
}
