<?php

namespace App\Services\Mailbox;

use App\Enums\Mailbox\MailboxProvider;
use App\Enums\Master\SmtpEncryption;
use App\Models\Mailbox\EmailAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MailboxAccountService
{
    /**
     * @param  array{
     *     email: string,
     *     display_name?: string|null,
     *     smtp_host: string,
     *     smtp_port: int,
     *     smtp_username?: string|null,
     *     smtp_password?: string|null,
     *     smtp_encryption?: string|null,
     *     imap_host?: string|null,
     *     imap_port: int,
     *     imap_username?: string|null,
     *     imap_password?: string|null,
     *     imap_encryption?: string|null,
     *     is_default?: bool
     * }  $data
     */
    public function createSmtpImapAccount(User $user, array $data): EmailAccount
    {
        return DB::transaction(function () use ($user, $data): EmailAccount {
            $smtpUsername = $data['smtp_username'] ?? $data['email'];
            $smtpPassword = $data['smtp_password'] ?? null;

            $account = EmailAccount::query()->create([
                'user_id' => $user->id,
                'provider' => MailboxProvider::SmtpImap,
                'email' => strtolower($data['email']),
                'display_name' => $data['display_name'] ?? $user->name,
                'is_active' => true,
                'is_default' => false,
                'smtp_host' => $data['smtp_host'],
                'smtp_port' => $data['smtp_port'],
                'smtp_username' => $smtpUsername,
                'smtp_password' => $smtpPassword,
                'smtp_encryption' => isset($data['smtp_encryption'])
                    ? SmtpEncryption::from($data['smtp_encryption'])
                    : SmtpEncryption::Tls,
                'imap_host' => filled($data['imap_host'] ?? null) ? $data['imap_host'] : $data['smtp_host'],
                'imap_port' => $data['imap_port'],
                'imap_username' => $data['imap_username'] ?? $smtpUsername,
                'imap_password' => $data['imap_password'] ?? $smtpPassword,
                'imap_encryption' => isset($data['imap_encryption'])
                    ? SmtpEncryption::from($data['imap_encryption'])
                    : SmtpEncryption::Ssl,
            ]);

            if (($data['is_default'] ?? false) || ! $this->userHasDefault($user)) {
                $this->makeDefault($account);
            }

            return $account->refresh();
        });
    }

    public function makeDefault(EmailAccount $account): void
    {
        DB::transaction(function () use ($account): void {
            EmailAccount::query()
                ->where('user_id', $account->user_id)
                ->where('id', '!=', $account->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);

            $account->update([
                'is_default' => true,
                'is_active' => true,
            ]);
        });
    }

    public function defaultFor(User $user): ?EmailAccount
    {
        return EmailAccount::query()
            ->ownedBy($user)
            ->active()
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();
    }

    public function delete(EmailAccount $account): void
    {
        $account->delete();
    }

    private function userHasDefault(User $user): bool
    {
        return EmailAccount::query()
            ->ownedBy($user)
            ->where('is_default', true)
            ->exists();
    }
}
