<?php

namespace Database\Factories;

use App\Enums\RegistrationStatus;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentRegistration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentRegistration>
 */
class StudentRegistrationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'school_id' => School::factory(),
            'nis' => fake()->unique()->numerify('########'),
            'entry_academic_year_id' => AcademicYear::factory(),
            'status' => RegistrationStatus::Aktif,
            'registered_at' => now()->toDateString(),
        ];
    }

    /**
     * Indicate that the registration belongs to the given student.
     */
    public function forStudent(Student $student): static
    {
        return $this->state(fn (array $attributes) => [
            'student_id' => $student->id,
        ]);
    }

    /**
     * Indicate that the registration belongs to the given school.
     */
    public function forSchool(School $school, ?AcademicYear $academicYear = null): static
    {
        return $this->state(fn (array $attributes) => [
            'school_id' => $school->id,
            'entry_academic_year_id' => $academicYear?->id ?? $attributes['entry_academic_year_id'],
        ]);
    }

    /**
     * Indicate that the registration has the given status.
     */
    public function withStatus(RegistrationStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }
}
