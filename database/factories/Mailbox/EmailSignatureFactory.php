<?php

namespace Database\Factories\Mailbox;

use App\Enums\Master\Status;
use App\Models\Mailbox\EmailSignature;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailSignature>
 */
class EmailSignatureFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'signature_name' => fake()->unique()->words(3, true),
            'signature_html' => '<p>'.fake()->sentence().'</p>',
            'is_default' => false,
            'status' => Status::Active,
        ];
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_default' => true,
            'status' => Status::Active,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => Status::Inactive,
            'is_default' => false,
        ]);
    }
}
