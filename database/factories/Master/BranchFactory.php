<?php

namespace Database\Factories\Master;

use App\Enums\Master\Status;
use App\Models\Master\Branch;
use App\Models\Master\Group;
use App\Models\Master\Region;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_name' => fake()->unique()->words(2, true),
            'geofencing' => false,
            'geofencing_range' => null,
            'branch_country_code' => null,
            'branch_fbcountry_code' => strtoupper(fake()->lexify('??')),
            'branch_manager' => User::factory(),
            'region_id' => Region::factory(),
            'country_id' => function (array $attributes): int {
                $region = Region::query()
                    ->whereKey((int) $attributes['region_id'])
                    ->firstOrFail();

                return $region->country_id;
            },
            'group_id' => Group::factory(),
            'branch_code' => strtoupper(fake()->unique()->bothify('BR###??')),
            'owner_id' => User::factory(),
            'man_power' => fake()->numberBetween(1, 50),
            'retail_man_power' => fake()->numberBetween(1, 20),
            'created_by' => User::factory(),
            'last_modified_by' => function (array $attributes): int {
                return (int) $attributes['created_by'];
            },
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
