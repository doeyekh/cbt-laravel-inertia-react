<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Student\Concerns\PresentsExamQuestions;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\ExamParticipant;
use App\Models\ExamSchedule;
use App\Models\Question;
use App\Services\ExamScheduleAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExamController extends Controller
{
    use PresentsExamQuestions;

    public function show(
        Request $request,
        ExamSchedule $examSchedule,
        ExamScheduleAccessService $examAccess,
    ): Response|RedirectResponse {
        $participant = ExamParticipant::query()
            ->findOrFail((int) $request->session()->get('student_participant_id'));

        $examSchedule->loadMissing(['examPackage', 'subject']);
        $examPackage = $examSchedule->examPackage;

        if ($examPackage === null || ! $examAccess->participantMayAccess($participant, $examSchedule, $examPackage)) {
            abort(404);
        }

        if (! $examAccess->isActive($examPackage, $examSchedule)) {
            abort(403);
        }

        $verifiedExamPackages = $request->session()->get('student_exam_packages', []);
        $storedHash = is_array($verifiedExamPackages)
            ? ($verifiedExamPackages[$examPackage->id] ?? null)
            : null;
        $currentHash = $examPackage->exam_access_token_hash;

        if (! is_string($storedHash) || ! is_string($currentHash) || ! hash_equals($currentHash, $storedHash)) {
            return to_route('student.exam-access.create', $examSchedule);
        }

        $attempt = ExamAttempt::query()
            ->where('exam_participant_id', $participant->id)
            ->where('exam_schedule_id', $examSchedule->id)
            ->with('answers')
            ->first();

        if ($attempt?->isSubmitted()) {
            return to_route('student.dashboard')->with('toast', [
                'type' => 'info',
                'title' => 'Ujian Sudah Dikumpulkan',
                'description' => 'Jawaban Anda sudah final dan tidak dapat diubah lagi.',
            ]);
        }

        $questionBank = $examAccess->resolveQuestionBank($examSchedule, $examPackage);

        if (! $questionBank) {
            abort(404);
        }

        $questions = Question::query()
            ->where('question_bank_id', $questionBank->id)
            ->where('is_active', true)
            ->with([
                'options' => fn ($query) => $query->orderBy('order'),
                'pairs' => fn ($query) => $query->orderBy('order'),
                'blanks' => fn ($query) => $query->orderBy('order'),
            ])
            ->orderBy('number')
            ->get();

        $answers = [];
        $flags = [];

        foreach ($attempt?->answers->sortBy('question_id') ?? [] as $answer) {
            $answerValue = ExamAnswer::restoreAnswer($answer->answer);
            $isLegacyFlag = $answerValue === 'flagged';

            if ($isLegacyFlag) {
                $answerValue = null;
            }

            $answers[$answer->question_id] = $answerValue;

            if ($answer->is_flagged || $isLegacyFlag) {
                $flags[] = (int) $answer->question_id;
            }
        }

        $durationSeconds = $attempt?->durationSeconds()
            ?? max(0, (int) $examSchedule->duration_minutes * 60);
        $remainingSeconds = $attempt?->remainingSeconds() ?? $durationSeconds;

        return Inertia::render('Student/ExamPage', [
            'exam' => $examSchedule,
            'questions' => $this->presentExamQuestions($questions),
            'answers' => $answers,
            'flags' => $flags,
            'remaining_seconds' => $remainingSeconds,
            'duration_seconds' => $durationSeconds,
            'deadline_at' => $attempt?->resolveDeadline()->toIso8601String(),
            'server_now' => now()->toIso8601String(),
        ]);
    }
}
