<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\ClassMember;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassMember>
 */
class ClassMemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'class_id' => SchoolClass::factory(),
            'student_id' => Student::factory(),
            'academic_year_id' => AcademicYear::factory(),
        ];
    }

    /**
     * Indicate that the membership belongs to the given class.
     */
    public function forClass(SchoolClass $schoolClass, ?Student $student = null): static
    {
        return $this->state(fn (array $attributes) => [
            'class_id' => $schoolClass->id,
            'student_id' => $student?->id ?? $attributes['student_id'],
            'academic_year_id' => $schoolClass->academic_year_id,
        ]);
    }
}
