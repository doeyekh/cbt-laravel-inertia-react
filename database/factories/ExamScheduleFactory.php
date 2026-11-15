<?php

namespace Database\Factories;

use App\Models\ExamPackage;
use App\Models\ExamSchedule;
use App\Models\SchoolClass;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamSchedule>
 */
class ExamScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exam_package_id' => ExamPackage::factory(),
            'class_id' => SchoolClass::factory(),
            'subject_id' => Subject::factory(),
            'exam_date' => fake()->dateTimeBetween('now', '+2 months')->format('Y-m-d'),
            'duration_minutes' => 90,
            'open_time' => '07:00:00',
            'close_time' => '08:30:00',
            'shuffle_questions' => true,
            'shuffle_options' => true,
            'show_score_to_student' => true,
            'status' => 'aktif',
        ];
    }

    /**
     * Indicate that the schedule belongs to the given exam package.
     */
    public function forPackage(ExamPackage $examPackage): static
    {
        return $this->state(fn (array $attributes) => [
            'exam_package_id' => $examPackage->id,
        ]);
    }
}
