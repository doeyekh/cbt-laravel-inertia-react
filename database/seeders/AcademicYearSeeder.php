<?php

namespace Database\Seeders;

use App\Enums\AcademicYearSemester;
use App\Models\AcademicYear;
use Illuminate\Database\Seeder;

class AcademicYearSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $startYears = [2025, 2024, 2023, 2022, 2021, 2020, 2019, 2018];
        $activeCode = '20241';

        foreach ($startYears as $startYear) {
            $name = "{$startYear}/".($startYear + 1);

            foreach (AcademicYearSemester::cases() as $semester) {
                $code = $startYear.$semester->codeSuffix();

                $academicYear = AcademicYear::firstOrNew([
                    'name' => $name,
                    'semester' => $semester->value,
                ]);

                $academicYear->forceFill([
                    'code' => $code,
                    'is_active' => $code === $activeCode,
                ])->save();
            }
        }
    }
}
