<?php

namespace Database\Factories\Master;

use App\Enums\Master\Status;
use App\Models\Master\Menu;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Menu>
 */
class MenuFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'menu_parent_id' => 0,
            'menu_name' => fake()->unique()->words(2, true),
            'column_description' => null,
            'menu_route' => '/'.fake()->unique()->slug(2),
            'menu_order' => fake()->numberBetween(1, 50),
            'screen_name' => null,
            'web_icon' => null,
            'mobile_icon' => null,
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
