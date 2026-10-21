<?php

namespace Database\Factories\Master;

use App\Enums\Master\EditorDriver;
use App\Enums\Master\Status;
use App\Models\Master\EditorSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EditorSetting>
 */
class EditorSettingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->randomElement(EditorDriver::cases()),
            'name' => fake()->words(2, true),
            'description' => fake()->optional()->sentence(),
            'is_default' => false,
            'status' => Status::Active,
            'sort_order' => fake()->numberBetween(0, 50),
        ];
    }

    public function tiptap(): static
    {
        return $this->state(fn (array $attributes): array => [
            'code' => EditorDriver::Tiptap,
            'name' => 'Tiptap',
            'sort_order' => 1,
        ]);
    }

    public function ckeditor(): static
    {
        return $this->state(fn (array $attributes): array => [
            'code' => EditorDriver::Ckeditor,
            'name' => 'CKEditor',
            'sort_order' => 2,
        ]);
    }

    public function tinymce(): static
    {
        return $this->state(fn (array $attributes): array => [
            'code' => EditorDriver::Tinymce,
            'name' => 'TinyMCE',
            'sort_order' => 3,
        ]);
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_default' => true,
            'status' => Status::Active,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => Status::Inactive,
            'is_default' => false,
        ]);
    }
}
