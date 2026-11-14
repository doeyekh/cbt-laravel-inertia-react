<?php

namespace Database\Factories;

use App\Models\School;
use App\Models\Session;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Session>
 */
class SessionFactory extends Factory
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
            'code' => fake()->unique()->bothify('SESS##'),
            'name' => fake()->unique()->word().' '.fake()->word(),
            'start_time' => '07:00:00',
            'end_time' => '09:00:00',
            'is_active' => false,
        ];
    }

    /**
     * Indicate that the session belongs to the given school.
     */
    public function forSchool(School $school): static
    {
        return $this->state(fn (array $attributes) => [
            'school_id' => $school->id,
        ]);
    }
}
