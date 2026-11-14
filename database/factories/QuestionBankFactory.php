<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\QuestionBank;
use App\Models\School;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<QuestionBank>
 */
class QuestionBankFactory extends Factory
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
            'subject_id' => Subject::factory(),
            'academic_year_id' => AcademicYear::query()
                ->where('is_active', true)
                ->orderByDesc('code')
                ->value('id') ?? AcademicYear::factory()->active(),
            'exam_type_id' => null,
            'code' => Str::upper(fake()->unique()->bothify('BSK-###')),
            'name' => 'Bank Soal '.fake()->unique()->words(3, true),
            'description' => fake()->sentence,
            'pilihan_ganda_count' => 10,
            'pilihan_ganda_kompleks_count' => 5,
            'menjodohkan_count' => 5,
            'isian_count' => 10,
            'esai_count' => 5,
            'pilihan_ganda_score' => 50,
            'pilihan_ganda_kompleks_score' => 30,
            'menjodohkan_score' => 40,
            'isian_score' => 20,
            'esai_score' => 10,
            'pilihan_ganda_option_count' => 4,
            'pilihan_ganda_kompleks_option_count' => 4,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the question bank belongs to the given academic year.
     */
    public function forAcademicYear(AcademicYear $academicYear): static
    {
        return $this->state(fn (array $attributes) => [
            'academic_year_id' => $academicYear->id,
        ]);
    }

    /**
     * Indicate that the question bank belongs to the given school.
     */
    public function forSchool(School $school): static
    {
        return $this->state(fn (array $attributes) => [
            'school_id' => $school->id,
        ]);
    }
}
