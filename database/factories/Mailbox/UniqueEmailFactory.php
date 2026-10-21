<?php

namespace Database\Factories\Mailbox;

use App\Models\Mailbox\UniqueEmail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UniqueEmail>
 */
class UniqueEmailFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email_id' => fake()->unique()->safeEmail(),
        ];
    }
}
