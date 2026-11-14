<?php

namespace Database\Seeders;

use App\Enums\ExamPackageStatus;
use App\Models\AcademicYear;
use App\Models\ExamPackage;
use App\Models\ExamType;
use App\Models\QuestionBank;
use App\Models\Room;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Session;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ExamPackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $school = School::query()->whereNull('deleted_at')->orderBy('id')->first();
        $academicYear = AcademicYear::query()
            ->orderByDesc('is_active')
            ->orderByDesc('code')
            ->first();

        if ($school === null || $academicYear === null) {
            return;
        }

        $examType = ExamType::query()
            ->where('school_id', $school->id)
            ->orderBy('id')
            ->first();

        $session = Session::query()
            ->where('school_id', $school->id)
            ->orderBy('start_time')
            ->first();

        $room = Room::query()
            ->where('school_id', $school->id)
            ->orderBy('name')
            ->first();

        $classes = SchoolClass::query()
            ->where('school_id', $school->id)
            ->where('academic_year_id', $academicYear->id)
            ->orderBy('code')
            ->limit(2)
            ->get();

        if ($examType === null || $session === null || $room === null || $classes->isEmpty()) {
            return;
        }

        $schedulableSubjectIds = QuestionBank::query()
            ->where('school_id', $school->id)
            ->where('academic_year_id', $academicYear->id)
            ->where('exam_type_id', $examType->id)
            ->whereHas('classes', fn ($query) => $query->whereIn('classes.id', $classes->pluck('id')))
            ->pluck('subject_id')
            ->unique();

        $subjects = Subject::query()
            ->whereIn('id', $schedulableSubjectIds)
            ->orderBy('code')
            ->limit(2)
            ->get();

        if ($subjects->isEmpty()) {
            return;
        }

        $startDate = now()->startOfMonth()->addDays(9)->toDateString();
        $endDate = now()->startOfMonth()->addDays(14)->toDateString();

        $examPackage = ExamPackage::query()->firstOrCreate(
            [
                'school_id' => $school->id,
                'code' => 'PTS-'.str_replace('/', '', $academicYear->name),
            ],
            [
                'academic_year_id' => $academicYear->id,
                'exam_type_id' => $examType->id,
                'name' => 'PTS '.$academicYear->name,
                'description' => 'Paket ujian tengah semester contoh.',
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => ExamPackageStatus::Draft,
            ]
        );

        $sequence = 0;

        $classes->each(function (SchoolClass $schoolClass) use ($examPackage, $subjects, $session, $room, $startDate, &$sequence): void {
            $subjects->each(function (Subject $subject) use ($examPackage, $schoolClass, $session, $room, $startDate, &$sequence): void {
                $examDate = Carbon::parse($startDate)->addDays($sequence)->toDateString();
                $sequence++;

                $examPackage->schedules()->firstOrCreate(
                    [
                        'class_id' => $schoolClass->id,
                        'subject_id' => $subject->id,
                    ],
                    [
                        'session_id' => $session->id,
                        'room_id' => $room->id,
                        'exam_date' => $examDate,
                        'duration_minutes' => 90,
                        'open_time' => $session->start_time,
                        'close_time' => $session->end_time,
                        'shuffle_questions' => true,
                        'shuffle_options' => true,
                        'show_score_to_student' => true,
                        'status' => 'aktif',
                    ]
                );
            });
        });
    }
}
