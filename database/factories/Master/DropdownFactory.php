<?php

namespace Database\Factories\Master;

use App\Enums\Master\Status;
use App\Models\Master\Dropdown;
use App\Models\Master\Module;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dropdown>
 */
class DropdownFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'module_id' => Module::factory(),
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
