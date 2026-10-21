<?php

namespace Database\Factories\Master;

use App\Enums\Master\Status;
use App\Models\Master\Module;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Module>
 */
class ModuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'module_name' => fake()->unique()->words(2, true),
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
