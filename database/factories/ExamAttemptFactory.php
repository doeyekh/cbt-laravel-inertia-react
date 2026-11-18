<?php

namespace Database\Factories;

use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\ExamParticipant;
use App\Models\ExamSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamAttempt>
 */
class ExamAttemptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exam_participant_id' => ExamParticipant::factory(),
            'exam_schedule_id' => ExamSchedule::factory(),
            'exam_package_id' => ExamPackage::factory(),
            'duration_minutes' => 90,
            'time_spent_seconds' => 0,
            'started_at' => now(),
            'submitted_at' => null,
        ];
    }
}
