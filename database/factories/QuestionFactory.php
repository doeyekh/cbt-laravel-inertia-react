<?php

namespace Database\Factories;

use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\School;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question_bank_id' => null,
            'school_id' => School::factory(),
            'subject_id' => Subject::factory(),
            'exam_type_id' => null,
            'class_id' => null,
            'created_by' => null,
            'type' => QuestionType::PilihanGanda,
            'number' => 1,
            'body' => fake()->sentence(12),
            'score' => 1,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the question belongs to the given school.
     */
    public function forSchool(School $school): static
    {
        return $this->state(fn (array $attributes) => [
            'school_id' => $school->id,
        ]);
    }

    /**
     * Indicate that the question has the given type.
     */
    public function withType(QuestionType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
        ]);
    }
}
