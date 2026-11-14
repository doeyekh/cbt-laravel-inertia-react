<?php

namespace Database\Seeders;

use App\Enums\Gender;
use App\Enums\RegistrationStatus;
use App\Models\AcademicYear;
use App\Models\ClassMember;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentRegistration;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $academicYear = AcademicYear::query()
            ->orderByDesc('is_active')
            ->orderByDesc('code')
            ->first();

        if ($academicYear === null) {
            return;
        }

        School::query()
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->limit(2)
            ->get()
            ->each(function (School $school) use ($academicYear): void {
                $schoolClasses = SchoolClass::query()
                    ->where('school_id', $school->id)
                    ->where('academic_year_id', $academicYear->id)
                    ->orderBy('code')
                    ->limit(2)
                    ->get();

                $schoolClasses->each(function (SchoolClass $schoolClass) use ($school, $academicYear): void {
                    foreach (range(1, 5) as $index) {
                        $nisn = str_pad((string) ($schoolClass->id * 100 + $index), 10, '0', STR_PAD_LEFT);

                        $student = Student::query()->firstOrCreate(
                            ['nisn' => $nisn],
                            [
                                'name' => fake()->name(),
                                'gender' => $index % 2 === 0 ? Gender::Perempuan : Gender::LakiLaki,
                                'birth_place' => fake()->city(),
                                'birth_date' => fake()->dateTimeBetween('-18 years', '-6 years')->format('Y-m-d'),
                            ]
                        );

                        StudentRegistration::query()->firstOrCreate(
                            [
                                'student_id' => $student->id,
                                'school_id' => $school->id,
                            ],
                            [
                                'nis' => $schoolClass->code.'-'.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
                                'entry_academic_year_id' => $academicYear->id,
                                'status' => RegistrationStatus::Aktif,
                                'registered_at' => now()->toDateString(),
                            ]
                        );

                        ClassMember::query()->firstOrCreate(
                            [
                                'student_id' => $student->id,
                                'academic_year_id' => $academicYear->id,
                            ],
                            [
                                'class_id' => $schoolClass->id,
                            ]
                        );
                    }
                });
            });
    }
}
