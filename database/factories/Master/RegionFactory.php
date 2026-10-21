<?php

namespace Database\Factories\Master;

use App\Enums\Master\Status;
use App\Models\Master\Country;
use App\Models\Master\Region;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Region>
 */
class RegionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'region_name' => fake()->unique()->words(2, true),
            'country_id' => Country::factory()->active(),
            'owner_id' => User::factory(),
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
