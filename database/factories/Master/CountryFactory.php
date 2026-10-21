<?php

namespace Database\Factories\Master;

use App\Enums\Master\CountryStatus;
use App\Models\Master\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Country>
 */
class CountryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sortname' => strtoupper(fake()->unique()->lexify('??')),
            'name' => fake()->unique()->country(),
            'currency' => fake()->currencyCode(),
            'phonecode' => fake()->numberBetween(1, 999),
            'status' => CountryStatus::Inactive,
            'timezone_id' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => CountryStatus::Active,
        ]);
    }
}
