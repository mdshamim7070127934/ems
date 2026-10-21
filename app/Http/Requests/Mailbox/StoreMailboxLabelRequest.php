<?php

namespace App\Http\Requests\Mailbox;

use App\Models\Mailbox\EmailLabel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMailboxLabelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', EmailLabel::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'account_id' => ['nullable', 'integer', 'exists:email_accounts,id'],
            'name' => ['required', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:20'],
        ];
    }
}
