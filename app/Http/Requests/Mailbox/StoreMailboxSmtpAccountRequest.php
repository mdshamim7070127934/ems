<?php

namespace App\Http\Requests\Mailbox;

use App\Enums\Master\SmtpEncryption;
use App\Models\Mailbox\EmailAccount;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMailboxSmtpAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', EmailAccount::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'smtp_host' => ['required', 'string', 'max:255'],
            'smtp_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string', 'max:255'],
            'smtp_encryption' => ['nullable', 'in:tls,ssl,none'],
            'imap_host' => ['nullable', 'string', 'max:255'],
            'imap_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'imap_username' => ['nullable', 'string', 'max:255'],
            'imap_password' => ['nullable', 'string', 'max:255'],
            'imap_encryption' => ['nullable', 'in:tls,ssl,none'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $payload = [];

        foreach (['smtp_encryption', 'imap_encryption'] as $field) {
            if ($this->exists($field) && blank($this->input($field))) {
                $payload[$field] = SmtpEncryption::None->value;
            }
        }

        foreach (['display_name', 'smtp_username', 'smtp_password', 'imap_host', 'imap_username', 'imap_password'] as $field) {
            if ($this->input($field) === '') {
                $payload[$field] = null;
            }
        }

        if (! $this->filled('smtp_port')) {
            $payload['smtp_port'] = 587;
        }

        if (! $this->filled('imap_port')) {
            $payload['imap_port'] = 993;
        }

        if ($payload !== []) {
            $this->merge($payload);
        }
    }
}
