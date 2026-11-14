<?php

namespace Database\Factories;

use App\Enums\AcademicYearSemester;
use App\Models\AcademicYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicYear>
 */
class AcademicYearFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startYear = fake()->unique()->numberBetween(1990, 2060);

        return [
            'name' => "{$startYear}/".($startYear + 1),
            'semester' => fake()->randomElement(AcademicYearSemester::cases()),
            'is_active' => false,
        ];
    }

    public function ganjil(): static
    {
        return $this->state(fn (array $attributes) => [
            'semester' => AcademicYearSemester::Ganjil,
        ]);
    }

    public function genap(): static
    {
        return $this->state(fn (array $attributes) => [
            'semester' => AcademicYearSemester::Genap,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
