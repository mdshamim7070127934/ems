<?php

namespace Database\Factories\Master;

use App\Enums\Master\Status;
use App\Models\Master\Country;
use App\Models\Master\State;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<State>
 */
class StateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'country_id' => Country::factory()->active(),
            'name' => fake()->unique()->words(2, true),
            'status' => Status::Active,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => Status::Inactive,
        ]);
    }
}
