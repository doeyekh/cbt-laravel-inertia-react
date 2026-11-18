<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Student\Concerns\PresentsExamQuestions;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\ExamParticipant;
use App\Models\ExamSchedule;
use App\Models\Question;
use App\Models\QuestionBank;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExamController extends Controller
{
    use PresentsExamQuestions;

    public function show(Request $request, ExamSchedule $examSchedule): Response|RedirectResponse
    {
        $participant = ExamParticipant::query()
            ->findOrFail((int) $request->session()->get('student_participant_id'));

        $examSchedule->loadMissing(['examPackage', 'subject']);
        $examPackage = $examSchedule->examPackage;

        if ($examPackage === null) {
            abort(404);
        }

        $verifiedExamPackages = $request->session()->get('student_exam_packages', []);
        $storedHash = is_array($verifiedExamPackages)
            ? ($verifiedExamPackages[$examPackage->id] ?? null)
            : null;
        $currentHash = $examPackage->exam_access_token_hash;

        if (! is_string($storedHash) || ! is_string($currentHash) || ! hash_equals($currentHash, $storedHash)) {
            return to_route('student.exam-access.create', $examSchedule);
        }

        $questionBank = QuestionBank::query()
            ->where('school_id', $examPackage->school_id)
            ->where('academic_year_id', $examPackage->academic_year_id)
            ->where('exam_type_id', $examPackage->exam_type_id)
            ->where('subject_id', $examSchedule->subject_id)
            ->first();

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

        $attempt = ExamAttempt::query()
            ->where('exam_participant_id', $participant->id)
            ->where('exam_schedule_id', $examSchedule->id)
            ->with('answers')
            ->first();

        $answers = $attempt?->answers
            ->mapWithKeys(fn (ExamAnswer $answer): array => [
                $answer->question_id => ExamAnswer::restoreAnswer($answer->answer),
            ])
            ->all() ?? [];
        $durationSeconds = $attempt?->durationSeconds()
            ?? max(0, (int) $examSchedule->duration_minutes * 60);
        $remainingSeconds = $attempt?->remainingSeconds() ?? $durationSeconds;

        return Inertia::render('Student/ExamPage', [
            'exam' => $examSchedule,
            'questions' => $this->presentExamQuestions($questions),
            'answers' => $answers,
            'remaining_seconds' => $remainingSeconds,
            'duration_seconds' => $durationSeconds,
            'deadline_at' => $attempt?->resolveDeadline()->toIso8601String(),
            'server_now' => now()->toIso8601String(),
        ]);
    }
}
