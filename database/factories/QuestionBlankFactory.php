<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\QuestionBlank;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionBlank>
 */
class QuestionBlankFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question_id' => Question::factory(),
            'answers' => [fake()->word()],
            'score' => null,
            'order' => 1,
        ];
    }
}
