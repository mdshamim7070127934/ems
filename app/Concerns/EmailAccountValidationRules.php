<?php

namespace App\Concerns;

use App\Enums\Master\EmailMailer;
use App\Enums\Master\EmailProvider;
use App\Enums\Master\SmtpEncryption;
use App\Enums\Master\Status;
use App\Models\Master\EmailAccount;
use App\Models\Master\EmailType;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

trait EmailAccountValidationRules
{
    /**
     * Normalize email account fields before validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'from_name' => trim((string) $this->input('from_name')),
            'from_email' => $this->filled('from_email')
                ? strtolower(trim((string) $this->input('from_email')))
                : $this->input('from_email'),
            'username' => $this->filled('username') ? trim((string) $this->input('username')) : null,
            'smtp_host' => $this->filled('smtp_host') ? trim((string) $this->input('smtp_host')) : null,
            'smtp_port' => $this->filled('smtp_port') ? $this->input('smtp_port') : null,
            'smtp_encryption' => $this->filled('smtp_encryption') ? $this->input('smtp_encryption') : null,
            'is_default' => $this->boolean('is_default'),
            'email_type_ids' => array_values(array_unique(array_map(
                intval(...),
                array_filter((array) $this->input('email_type_ids', [])),
            ))),
        ]);

        if ($this->has('password') && $this->input('password') === '') {
            $this->merge(['password' => null]);
        }
    }

    /**
     * Get the validation rules used to validate email accounts.
     *
     * @return array<string, mixed>
     */
    protected function emailAccountRules(?int $id = null, bool $passwordRequired = true): array
    {
        if ($this->oauthAccount()?->usesOAuth()) {
            return [
                'name' => ['required', 'string', 'max:150', Rule::unique('mst_email_account', 'name')->ignore($id)],
                'from_name' => ['required', 'string', 'max:150'],
                'status' => ['required', Rule::enum(Status::class)],
                'is_default' => ['required', 'boolean'],
                'email_type_ids' => ['present', 'array'],
                'email_type_ids.*' => ['integer', Rule::exists('mst_email_type', 'id')],
            ];
        }

        return [
            'name' => ['required', 'string', 'max:150', Rule::unique('mst_email_account', 'name')->ignore($id)],
            'provider' => ['required', Rule::in([EmailProvider::Smtp->value, EmailProvider::Other->value])],
            'mailer' => ['required', Rule::in([EmailMailer::Smtp->value])],
            'from_name' => ['required', 'string', 'max:150'],
            'from_email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('mst_email_account', 'from_email')
                    ->where('provider', $this->input('provider'))
                    ->ignore($id),
            ],
            'username' => ['required', 'string', 'max:255'],
            'password' => $passwordRequired
                ? ['required', 'string', 'max:2000']
                : ['nullable', 'string', 'max:2000'],
            'smtp_host' => ['required', 'string', 'max:255'],
            'smtp_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'smtp_encryption' => ['required', Rule::enum(SmtpEncryption::class)],
            'status' => ['required', Rule::enum(Status::class)],
            'is_default' => ['required', 'boolean'],
            'email_type_ids' => ['present', 'array'],
            'email_type_ids.*' => ['integer', Rule::exists('mst_email_type', 'id')],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->boolean('is_default') && $this->input('status') === Status::Inactive->value) {
                    $validator->errors()->add(
                        'is_default',
                        'An inactive email account cannot be the default sender.',
                    );
                }

                if ($validator->errors()->has('email_type_ids') || $validator->errors()->has('email_type_ids.*')) {
                    return;
                }

                $account = $this->oauthAccount();
                $assignedIds = $account instanceof EmailAccount
                    ? $account->emailTypes()->pluck('mst_email_type.id')->map(intval(...))->all()
                    : [];

                $inactiveIds = EmailType::query()
                    ->whereIn('id', $this->input('email_type_ids', []))
                    ->where('status', Status::Inactive)
                    ->pluck('id')
                    ->map(intval(...))
                    ->all();

                $newInactiveIds = array_values(array_diff($inactiveIds, $assignedIds));

                if ($newInactiveIds !== []) {
                    $validator->errors()->add(
                        'email_type_ids',
                        'Inactive email types cannot be assigned to new mappings.',
                    );
                }
            },
        ];
    }

    protected function oauthAccount(): ?EmailAccount
    {
        $account = $this->route('email_account');

        return $account instanceof EmailAccount ? $account : null;
    }
}
