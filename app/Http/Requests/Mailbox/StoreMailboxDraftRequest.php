<?php

namespace App\Http\Requests\Mailbox;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMailboxDraftRequest extends FormRequest
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
        $maxKilobytes = (int) config('mailbox.attachments_max_kilobytes', 25600);
        $extensions = implode(',', config('mailbox.attachments_allowed_extensions', []));

        return [
            'draft_id' => ['nullable', 'integer', 'exists:email_drafts,id'],
            'to_emails' => [Rule::requiredIf($this->boolean('send')), 'nullable', 'array'],
            'to_emails.*' => ['email'],
            'autosave' => ['sometimes', 'boolean'],
            'cc_emails' => ['nullable', 'array'],
            'cc_emails.*' => ['email'],
            'bcc_emails' => ['nullable', 'array'],
            'bcc_emails.*' => ['email'],
            'subject' => ['nullable', 'string', 'max:1000'],
            'body_html' => ['nullable', 'string'],
            'thread_id' => ['nullable', 'integer', 'exists:email_threads,id'],
            'in_reply_to' => ['nullable', 'string', 'max:500'],
            'keep_attachment_ids' => ['nullable', 'array'],
            'keep_attachment_ids.*' => ['integer'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:'.$maxKilobytes, 'extensions:'.$extensions],
            'send' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['to_emails', 'cc_emails', 'bcc_emails'] as $field) {
            $value = $this->input($field);

            if (is_string($value)) {
                $this->merge([
                    $field => $value === ''
                        ? []
                        : array_values(array_filter(array_map('trim', explode(',', $value)))),
                ]);
            }
        }

        if ($this->exists('send')) {
            $this->merge([
                'send' => $this->boolean('send'),
            ]);
        }

        if ($this->exists('keep_attachment_ids') && ! is_array($this->input('keep_attachment_ids'))) {
            $this->merge([
                'keep_attachment_ids' => [],
            ]);
        }
    }
}
