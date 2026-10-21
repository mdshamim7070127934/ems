<?php

namespace Database\Factories\Mailbox;

use App\Models\Mailbox\EmailAccount;
use App\Models\Mailbox\EmailLabel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<EmailLabel>
 */
class EmailLabelFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'user_id' => User::factory(),
            'account_id' => EmailAccount::factory(),
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'color' => '#3b82f6',
        ];
    }
}
