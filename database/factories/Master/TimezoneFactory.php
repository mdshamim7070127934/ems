<?php

namespace Database\Factories\Master;

use App\Enums\Master\Status;
use App\Models\Master\Timezone;
use DateTimeZone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Timezone>
 */
class TimezoneFactory extends Factory
{
    private static int $identifierIndex = 0;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            ...Timezone::attributesFor($this->nextIdentifier()),
            'status' => Status::Active,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => Status::Inactive,
        ]);
    }

    private function nextIdentifier(): string
    {
        $identifiers = DateTimeZone::listIdentifiers();
        $name = $identifiers[self::$identifierIndex % count($identifiers)];
        self::$identifierIndex++;

        return $name;
    }
}
