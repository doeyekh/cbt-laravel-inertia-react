<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\SchoolClass;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolClass>
 */
class SchoolClassFactory extends Factory
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
            'academic_year_id' => AcademicYear::factory(),
            'code' => fake()->unique()->bothify('KLS-##'),
            'name' => fake()->unique()->sentence(2),
            'is_active' => false,
        ];
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

    public function forSchool(School $school, ?int $academicYearId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'school_id' => $school->id,
            'academic_year_id' => $academicYearId ?? $attributes['academic_year_id'],
        ]);
    }
}
