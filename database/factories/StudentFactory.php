<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nisn' => fake()->unique()->numerify('##########'),
            'name' => fake()->name(),
            'gender' => fake()->randomElement(Gender::cases()),
            'birth_place' => fake()->city(),
            'birth_date' => fake()->dateTimeBetween('-20 years', '-6 years')->format('Y-m-d'),
            'photo_path' => null,
        ];
    }

    /**
     * Indicate that the student is male.
     */
    public function male(): static
    {
        return $this->state(fn (array $attributes) => [
            'gender' => Gender::LakiLaki,
        ]);
    }

    /**
     * Indicate that the student is female.
     */
    public function female(): static
    {
        return $this->state(fn (array $attributes) => [
            'gender' => Gender::Perempuan,
        ]);
    }
}
