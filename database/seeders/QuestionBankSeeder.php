<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\QuestionBank;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class QuestionBankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $school = School::query()->whereNull('deleted_at')->orderBy('id')->first();
        $subject = Subject::query()->orderBy('id')->first();
        $academicYear = AcademicYear::active();

        if ($school === null || $subject === null || $academicYear === null) {
            return;
        }

        $classes = SchoolClass::query()
            ->where('school_id', $school->id)
            ->where('academic_year_id', $academicYear->id)
            ->orderBy('code')
            ->limit(3)
            ->get();

        if ($classes->isEmpty()) {
            return;
        }

        $questionBank = QuestionBank::query()->firstOrCreate(
            [
                'school_id' => $school->id,
                'code' => 'BSK-MAT-01',
            ],
            [
                'subject_id' => $subject->id,
                'academic_year_id' => $academicYear->id,
                'name' => 'Bank Soal Matematika',
                'description' => 'Bank soal contoh untuk mata pelajaran matematika.',
                'pilihan_ganda_count' => 20,
                'pilihan_ganda_kompleks_count' => 10,
                'menjodohkan_count' => 10,
                'isian_count' => 10,
                'esai_count' => 5,
                'is_active' => true,
            ]
        );

        $questionBank->classes()->syncWithoutDetaching($classes->pluck('id'));
    }
}
