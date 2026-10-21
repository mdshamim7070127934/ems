<?php

namespace Database\Factories\Mailbox;

use App\Models\Mailbox\EmailAccount;
use App\Models\Mailbox\EmailThread;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailThread>
 */
class EmailThreadFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'account_id' => EmailAccount::factory(),
            'thread_id' => fake()->unique()->uuid(),
            'subject' => fake()->sentence(),
            'snippet' => fake()->sentence(),
            'last_message_at' => now(),
            'message_count' => 1,
            'is_starred' => false,
        ];
    }
}
