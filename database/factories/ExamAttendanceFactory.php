<?php

namespace Database\Factories;

use App\Models\ExamAttendance;
use App\Models\ExamParticipant;
use App\Models\ExamSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamAttendance>
 */
class ExamAttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exam_schedule_id' => ExamSchedule::factory(),
            'exam_participant_id' => ExamParticipant::factory(),
            'status' => 'tidak_hadir',
            'check_in_time' => null,
        ];
    }
}
