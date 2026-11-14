<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\SubjectTeacher;
use App\Models\User;
use Illuminate\Database\Seeder;

class SubjectTeacherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $academicYear = AcademicYear::active();

        if ($academicYear === null) {
            return;
        }

        $teacher = User::query()
            ->where('role', UserRole::Guru->value)
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        if ($teacher === null) {
            return;
        }

        SchoolClass::query()
            ->where('academic_year_id', $academicYear->id)
            ->orderBy('code')
            ->get()
            ->each(function (SchoolClass $schoolClass) use ($academicYear, $teacher): void {
                Subject::query()
                    ->where('is_active', true)
                    ->limit(3)
                    ->get()
                    ->each(function (Subject $subject) use ($schoolClass, $academicYear, $teacher): void {
                        SubjectTeacher::firstOrCreate(
                            [
                                'school_class_id' => $schoolClass->id,
                                'academic_year_id' => $academicYear->id,
                                'subject_id' => $subject->id,
                            ],
                            [
                                'teacher_id' => $teacher->id,
                                'is_active' => true,
                            ]
                        );
                    });
            });
    }
}
