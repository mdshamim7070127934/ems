<?php

namespace Database\Factories\Mailbox;

use App\Enums\Mailbox\MailboxProvider;
use App\Enums\Master\SmtpEncryption;
use App\Models\Mailbox\EmailAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailAccount>
 */
class EmailAccountFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $email = fake()->unique()->safeEmail();

        return [
            'user_id' => User::factory(),
            'provider' => MailboxProvider::SmtpImap,
            'email' => $email,
            'display_name' => fake()->name(),
            'is_default' => false,
            'is_active' => true,
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => 587,
            'smtp_username' => $email,
            'smtp_password' => 'secret-password',
            'smtp_encryption' => SmtpEncryption::Tls,
            'imap_host' => 'imap.example.com',
            'imap_port' => 993,
            'imap_username' => $email,
            'imap_password' => 'secret-password',
            'imap_encryption' => SmtpEncryption::Ssl,
        ];
    }

    public function gmail(): static
    {
        return $this->state(fn (array $attributes): array => [
            'provider' => MailboxProvider::Gmail,
            'access_token' => 'mailbox-gmail-access',
            'refresh_token' => 'mailbox-gmail-refresh',
            'token_expiry' => now()->addHour(),
            'smtp_host' => null,
            'smtp_port' => null,
            'smtp_username' => null,
            'smtp_password' => null,
            'smtp_encryption' => null,
            'imap_host' => null,
            'imap_port' => null,
            'imap_username' => null,
            'imap_password' => null,
            'imap_encryption' => null,
        ]);
    }

    public function outlook(): static
    {
        return $this->state(fn (array $attributes): array => [
            'provider' => MailboxProvider::Outlook,
            'access_token' => 'mailbox-outlook-access',
            'refresh_token' => 'mailbox-outlook-refresh',
            'token_expiry' => now()->addHour(),
            'smtp_host' => null,
            'smtp_port' => null,
            'smtp_username' => null,
            'smtp_password' => null,
            'smtp_encryption' => null,
            'imap_host' => null,
            'imap_port' => null,
            'imap_username' => null,
            'imap_password' => null,
            'imap_encryption' => null,
        ]);
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
            'is_default' => false,
        ]);
    }
}
