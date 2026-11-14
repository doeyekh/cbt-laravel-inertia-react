<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\ExamParticipant;
use App\Models\School;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ExamParticipant>
 */
class ExamParticipantFactory extends Factory
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
            'academic_year_id' => AcademicYear::factory(),
            'session_id' => null,
            'room_id' => null,
            'username' => Str::upper(fake()->unique()->bothify('U##??')),
            'participant_number' => Str::upper(fake()->unique()->bothify('P-###')),
            'password' => 'rahasia123',
            'password_plain' => 'rahasia123',
        ];
    }

    /**
     * Indicate that the participant belongs to the given school and academic year.
     */
    public function forSchoolYear(School $school, AcademicYear $academicYear, ?Student $student = null): static
    {
        return $this->state(fn (array $attributes) => [
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'student_id' => $student?->id ?? $attributes['student_id'],
        ]);
    }

    /**
     * Indicate that the participant sits in the given room and session.
     */
    public function placedIn(int $roomId, int $sessionId): static
    {
        return $this->state(fn (array $attributes) => [
            'room_id' => $roomId,
            'session_id' => $sessionId,
        ]);
    }

    /**
     * Indicate that the credential has not been generated yet.
     */
    public function withoutCredential(): static
    {
        return $this->state(fn (): array => [
            'username' => null,
            'participant_number' => null,
            'password' => null,
            'password_plain' => null,
        ]);
    }
}
