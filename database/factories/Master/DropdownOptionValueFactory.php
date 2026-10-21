<?php

namespace Database\Factories\Master;

use App\Enums\Master\Status;
use App\Models\Master\Dropdown;
use App\Models\Master\DropdownOptionValue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DropdownOptionValue>
 */
class DropdownOptionValueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'option_type_id' => Dropdown::factory(),
            'module_id' => function (array $attributes): int {
                $dropdown = Dropdown::query()
                    ->whereKey((int) $attributes['option_type_id'])
                    ->firstOrFail();

                return $dropdown->module_id;
            },
            'parent_id' => 0,
            'option_value' => fake()->unique()->words(2, true),
            'position' => fake()->optional()->numberBetween(1, 50),
            'user_access_id' => null,
            'master_type' => null,
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
