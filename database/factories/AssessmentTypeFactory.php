<?php

namespace Database\Factories;

use App\Models\AssessmentType;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AssessmentType>
 */
class AssessmentTypeFactory extends Factory
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
            'code' => Str::upper(fake()->unique()->lexify('???')).'-'.fake()->numberBetween(10, 99),
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->sentence(),
            'weight' => fake()->randomFloat(2, 5, 40),
            'is_active' => false,
        ];
    }

    /**
     * Indicate that the assessment type is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the assessment type is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
