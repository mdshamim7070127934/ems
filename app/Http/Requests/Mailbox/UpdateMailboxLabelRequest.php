<?php

namespace App\Http\Requests\Mailbox;

use App\Models\Mailbox\EmailLabel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMailboxLabelRequest extends FormRequest
{
    public function authorize(): bool
    {
        $label = $this->route('mailbox_label');

        return $label instanceof EmailLabel
            && ($this->user()?->can('update', $label) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:20'],
        ];
    }
}
