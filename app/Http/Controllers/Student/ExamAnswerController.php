<?php

namespace App\Http\Controllers\Student;

use App\Enums\ExamPackageStatus;
use App\Enums\ExamScheduleStatus;
use App\Http\Controllers\Controller;
use App\Models\ClassMember;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\ExamParticipant;
use App\Models\ExamSchedule;
use App\Models\QuestionBank;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ExamAnswerController extends Controller
{
    /**
     * Store or update the student's answers for an exam schedule.
     */
    public function store(Request $request, ExamSchedule $examSchedule): JsonResponse
    {
        $participant = ExamParticipant::query()
            ->findOrFail((int) $request->session()->get('student_participant_id'));

        $examSchedule->loadMissing('examPackage');
        $examPackage = $examSchedule->examPackage;

        if ($examPackage === null || ! $this->participantMayAccessSchedule($participant, $examSchedule, $examPackage)) {
            return response()->json(['message' => 'Akses tidak diizinkan untuk jadwal ujian ini.'], 403);
        }

        if (
            $examPackage->status !== ExamPackageStatus::Aktif
            || $examSchedule->status !== ExamScheduleStatus::Aktif
            || ! $this->scheduleIsOpen($examPackage, $examSchedule)
        ) {
            return response()->json(['message' => 'Jadwal ujian belum dibuka atau sudah ditutup.'], 403);
        }

        $verifiedExamPackages = $request->session()->get('student_exam_packages', []);
        $storedHash = is_array($verifiedExamPackages)
            ? ($verifiedExamPackages[$examPackage->id] ?? null)
            : null;
        $currentHash = $examPackage->exam_access_token_hash;

        if (! is_string($storedHash) || ! is_string($currentHash) || ! hash_equals($currentHash, $storedHash)) {
            return response()->json(['message' => 'Token ujian belum diverifikasi.'], 403);
        }

        $request->validate([
            'answers' => 'required|array',
            'answers.*' => 'nullable|string',
            'time_spent_seconds' => 'required|integer|min:0',
        ]);

        $questionBank = QuestionBank::query()
            ->where('school_id', $examPackage->school_id)
            ->where('academic_year_id', $examPackage->academic_year_id)
            ->where('exam_type_id', $examPackage->exam_type_id)
            ->where('subject_id', $examSchedule->subject_id)
            ->first();

        $validQuestionIds = $questionBank?->questions
            ->where('is_active', true)
            ->pluck('id')
            ->toArray() ?? [];

        $attempt = ExamAttempt::updateOrCreate(
            [
                'exam_participant_id' => $participant->id,
                'exam_schedule_id' => $examSchedule->id,
            ],
            [
                'exam_package_id' => $examPackage->id,
                'duration_minutes' => $examSchedule->duration_minutes,
                'time_spent_seconds' => $request->integer('time_spent_seconds'),
                'started_at' => now(),
                'submitted_at' => now(),
            ]
        );

        $answers = $request->array('answers');

        foreach ($answers as $questionId => $answer) {
            $questionId = (int) $questionId;

            if (! in_array($questionId, $validQuestionIds, true)) {
                continue;
            }

            ExamAnswer::updateOrCreate(
                [
                    'exam_attempt_id' => $attempt->id,
                    'question_id' => $questionId,
                ],
                [
                    'question_no' => $this->getQuestionNo($questionBank, $questionId),
                    'answer' => $answer,
                ]
            );
        }

        return response()->json([
            'message' => 'Jawaban berhasil disimpan.',
            'attempt_id' => $attempt->id,
        ]);
    }

    private function participantMayAccessSchedule(
        ExamParticipant $participant,
        ExamSchedule $examSchedule,
        ExamPackage $examPackage,
    ): bool {
        if (
            (int) $participant->school_id !== (int) $examPackage->school_id
            || (int) $participant->academic_year_id !== (int) $examPackage->academic_year_id
        ) {
            return false;
        }

        if (
            ($examSchedule->session_id !== null && (int) $participant->session_id !== (int) $examSchedule->session_id)
            || ($examSchedule->room_id !== null && (int) $participant->room_id !== (int) $examSchedule->room_id)
        ) {
            return false;
        }

        return ClassMember::query()
            ->where('student_id', $participant->student_id)
            ->where('academic_year_id', $examPackage->academic_year_id)
            ->where('class_id', $examSchedule->class_id)
            ->whereHas('schoolClass', fn ($query) => $query
                ->where('school_id', $examPackage->school_id)
                ->where('academic_year_id', $examPackage->academic_year_id))
            ->exists();
    }

    private function scheduleIsOpen(ExamPackage $examPackage, ExamSchedule $examSchedule): bool
    {
        if (
            $examSchedule->exam_date->lt($examPackage->start_date)
            || $examSchedule->exam_date->gt($examPackage->end_date)
        ) {
            return false;
        }

        $examDate = $examSchedule->exam_date->toDateString();
        $opensAt = Carbon::parse("{$examDate} {$examSchedule->open_time}");
        $closesAt = Carbon::parse("{$examDate} {$examSchedule->close_time}");
        $currentTime = now();

        return $currentTime->gte($opensAt) && $currentTime->lte($closesAt);
    }

    private function getQuestionNo(?QuestionBank $questionBank, int $questionId): int
    {
        if (! $questionBank) {
            return 0;
        }

        return $questionBank->questions
            ->where('id', $questionId)
            ->first()?->number ?? 0;
    }
}
