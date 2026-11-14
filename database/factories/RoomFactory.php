<?php

namespace Database\Factories;

use App\Models\Room;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'code' => fake()->unique()->bothify('RM##'),
            'name' => fake()->unique()->word().' '.fake()->word(),
            'building' => fake()->randomElement(['Gedung A', 'Gedung B', 'Gedung C']),
            'floor' => fake()->randomElement(['1', '2', '3']),
            'capacity' => fake()->numberBetween(20, 100),
            'is_active' => false,
        ];
    }

    /**
     * Indicate that the room belongs to the given school.
     */
    public function forSchool(School $school): static
    {
        return $this->state(fn (array $attributes) => [
            'school_id' => $school->id,
        ]);
    }
}
