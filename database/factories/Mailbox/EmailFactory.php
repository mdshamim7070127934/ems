<?php

namespace Database\Factories\Mailbox;

use App\Models\Mailbox\Email;
use App\Models\Mailbox\EmailAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Email>
 */
class EmailFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'account_id' => EmailAccount::factory(),
            'thread_id' => null,
            'folder_id' => null,
            'message_id' => fake()->unique()->uuid().'@mail.example',
            'provider_message_id' => fake()->unique()->uuid(),
            'from_email' => fake()->safeEmail(),
            'from_name' => fake()->name(),
            'to_emails' => [fake()->safeEmail()],
            'cc_emails' => [],
            'bcc_emails' => [],
            'subject' => fake()->sentence(),
            'snippet' => fake()->sentence(),
            'body_html' => '<p>'.fake()->paragraph().'</p>',
            'body_plain' => fake()->paragraph(),
            'internal_date' => now(),
            'is_unread' => true,
            'is_starred' => false,
            'is_draft' => false,
        ];
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_unread' => false,
        ]);
    }

    public function starred(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_starred' => true,
        ]);
    }
}
