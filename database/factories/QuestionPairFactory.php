<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\QuestionPair;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionPair>
 */
class QuestionPairFactory extends Factory
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
            'left_text' => fake()->word(),
            'right_text' => fake()->word(),
            'order' => 1,
        ];
    }
}
