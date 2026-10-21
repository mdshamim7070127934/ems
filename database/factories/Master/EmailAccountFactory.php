<?php

namespace Database\Factories\Master;

use App\Enums\Master\EmailMailer;
use App\Enums\Master\EmailProvider;
use App\Enums\Master\SmtpEncryption;
use App\Enums\Master\Status;
use App\Models\Master\EmailAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailAccount>
 */
class EmailAccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $email = fake()->unique()->safeEmail();

        return [
            'name' => fake()->unique()->company().' Mail',
            'provider' => EmailProvider::Smtp,
            'mailer' => EmailMailer::Smtp,
            'from_name' => fake()->company(),
            'from_email' => $email,
            'username' => $email,
            'password' => 'secret-password',
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => 587,
            'smtp_encryption' => SmtpEncryption::Tls,
            'status' => Status::Active,
            'is_default' => false,
        ];
    }

    public function gmailOAuth(): static
    {
        return $this->oauth(EmailProvider::Gmail, EmailMailer::GmailApi);
    }

    public function outlookOAuth(): static
    {
        return $this->oauth(EmailProvider::Outlook, EmailMailer::GraphApi);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => Status::Inactive,
            'is_default' => false,
        ]);
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_default' => true,
            'status' => Status::Active,
        ]);
    }

    private function oauth(EmailProvider $provider, EmailMailer $mailer): static
    {
        return $this->state(fn (array $attributes): array => [
            'provider' => $provider,
            'mailer' => $mailer,
            'username' => null,
            'password' => null,
            'smtp_host' => null,
            'smtp_port' => null,
            'smtp_encryption' => null,
            'oauth_provider_account_id' => fake()->unique()->uuid(),
            'oauth_access_token' => 'oauth-access-token',
            'oauth_refresh_token' => 'oauth-refresh-token',
            'oauth_token_expires_at' => now()->addHour(),
            'last_token_refresh_at' => now(),
            'reauthorization_required' => false,
            'oauth_error' => null,
        ]);
    }
}
