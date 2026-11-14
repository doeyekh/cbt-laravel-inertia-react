<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\SubjectTeacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubjectTeacher>
 */
class SubjectTeacherFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_class_id' => SchoolClass::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'subject_id' => Subject::factory(),
            'teacher_id' => User::factory()->guru(),
            'is_active' => false,
        ];
    }

    /**
     * Indicate that the subject teacher belongs to the given class.
     */
    public function forClass(
        SchoolClass $schoolClass,
        ?AcademicYear $academicYear = null,
        ?Subject $subject = null,
        ?User $teacher = null
    ): static {
        return $this->state(fn (array $attributes) => [
            'school_class_id' => $schoolClass->id,
            'academic_year_id' => $academicYear?->id ?? $attributes['academic_year_id'],
            'subject_id' => $subject?->id ?? $attributes['subject_id'],
            'teacher_id' => $teacher?->id ?? $attributes['teacher_id'],
        ]);
    }
}
