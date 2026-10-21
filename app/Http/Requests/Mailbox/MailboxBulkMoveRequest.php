<?php

namespace App\Http\Requests\Mailbox;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class MailboxBulkMoveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'thread_ids' => ['required', 'array', 'min:1'],
            'thread_ids.*' => ['integer', 'exists:email_threads,id'],
            'folder_id' => ['required', 'integer', 'exists:email_folders,id'],
        ];
    }
}
