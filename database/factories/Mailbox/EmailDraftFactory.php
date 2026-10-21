<?php

namespace Database\Factories\Mailbox;

use App\Models\Mailbox\EmailAccount;
use App\Models\Mailbox\EmailDraft;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailDraft>
 */
class EmailDraftFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'account_id' => EmailAccount::factory(),
            'to_emails' => [fake()->safeEmail()],
            'cc_emails' => [],
            'bcc_emails' => [],
            'subject' => fake()->sentence(),
            'body_html' => '<p>'.fake()->paragraph().'</p>',
            'body_plain' => fake()->paragraph(),
        ];
    }
}
