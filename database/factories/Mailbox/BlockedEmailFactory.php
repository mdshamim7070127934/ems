<?php

namespace Database\Factories\Mailbox;

use App\Models\Mailbox\BlockedEmail;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BlockedEmail>
 */
class BlockedEmailFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::factory();

        return [
            'user_id' => $user,
            'email_id' => fake()->unique()->safeEmail(),
            'is_block' => true,
            'blocked_by' => $user,
        ];
    }
}
