<?php

namespace Database\Factories\Master;

use App\Enums\Master\Status;
use App\Models\Master\City;
use App\Models\Master\State;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<City>
 */
class CityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'state_id' => State::factory(),
            'country_id' => function (array $attributes): int {
                $state = State::query()
                    ->whereKey((int) $attributes['state_id'])
                    ->firstOrFail();

                return $state->country_id;
            },
            'name' => fake()->unique()->city(),
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
