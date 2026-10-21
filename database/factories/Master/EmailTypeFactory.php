<?php

namespace Database\Factories\Master;

use App\Enums\Master\Status;
use App\Models\Master\EmailType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailType>
 */
class EmailTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'code' => strtoupper(str_replace(' ', '_', $name)).'_'.fake()->unique()->numerify('###'),
            'name' => ucwords($name),
            'description' => fake()->optional()->sentence(),
            'status' => Status::Active,
            'sort_order' => fake()->numberBetween(0, 50),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => Status::Inactive,
        ]);
    }

    public function registration(): static
    {
        return $this->state(fn (array $attributes): array => [
            'code' => EmailType::CODE_REGISTRATION,
            'name' => 'Registration',
            'sort_order' => 1,
        ]);
    }
}
