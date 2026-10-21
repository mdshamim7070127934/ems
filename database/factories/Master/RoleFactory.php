<?php

namespace Database\Factories\Master;

use App\Models\Master\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->lexify('role_????');

        return [
            'role_name' => $name,
            'display_name' => fake()->unique()->words(2, true),
            'is_active' => true,
            'is_super_admin' => false,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role_name' => 'admin',
            'display_name' => 'Administrator',
            'is_active' => true,
            'is_super_admin' => true,
        ]);
    }
}
