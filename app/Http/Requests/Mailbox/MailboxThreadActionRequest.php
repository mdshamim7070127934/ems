<?php

namespace App\Http\Requests\Mailbox;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class MailboxThreadActionRequest extends FormRequest
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
            'action' => ['required', 'in:read,unread,star,unstar,trash,spam'],
            'email_id' => ['nullable', 'integer', 'exists:emails,id'],
            'thread_id' => ['nullable', 'integer', 'exists:email_threads,id'],
        ];
    }
}
