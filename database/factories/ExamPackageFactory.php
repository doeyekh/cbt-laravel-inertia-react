<?php

namespace Database\Factories;

use App\Enums\ExamPackageStatus;
use App\Models\AcademicYear;
use App\Models\ExamPackage;
use App\Models\ExamType;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ExamPackage>
 */
class ExamPackageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('now', '+2 months');
        $endDate = (clone $startDate)->modify('+7 days');

        return [
            'school_id' => School::factory(),
            'academic_year_id' => AcademicYear::query()
                ->where('is_active', true)
                ->orderByDesc('code')
                ->value('id') ?? AcademicYear::factory()->active(),
            'exam_type_id' => ExamType::factory(),
            'code' => Str::upper(fake()->unique()->bothify('PKT-###')),
            'name' => 'Paket '.fake()->unique()->words(3, true),
            'description' => fake()->sentence,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'status' => ExamPackageStatus::Draft,
        ];
    }

    /**
     * Indicate that the package belongs to the given school.
     */
    public function forSchool(School $school, ?AcademicYear $academicYear = null, ?ExamType $examType = null): static
    {
        return $this->state(fn (array $attributes) => [
            'school_id' => $school->id,
            'academic_year_id' => $academicYear?->id ?? $attributes['academic_year_id'],
            'exam_type_id' => $examType?->id ?? $attributes['exam_type_id'],
        ]);
    }

    /**
     * Indicate that the package has the given status.
     */
    public function withStatus(ExamPackageStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }
}
