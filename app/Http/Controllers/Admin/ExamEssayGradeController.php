<?php

namespace App\Http\Controllers\Admin;

use App\Enums\QuestionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GradeExamEssayRequest;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Services\GradeExamAttemptService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExamEssayGradeController extends Controller
{
    public function index(Request $request, ExamAttempt $examAttempt): Response
    {
        $this->authorizeAttemptSchool($request, $examAttempt);

        abort_if($examAttempt->status?->allowsAnswerChanges(), 404);

        $examAttempt->load([
            'participant.student',
            'examSchedule.subject',
            'answers.question',
        ]);

        return Inertia::render('ExamAttempts/GradeEssays', [
            'attempt' => [
                'id' => $examAttempt->id,
                'student_name' => $examAttempt->participant?->student?->name,
                'subject' => $examAttempt->examSchedule?->subject?->name,
                'status' => $examAttempt->status->value,
                'score' => $examAttempt->score,
                'max_score' => $examAttempt->max_score,
            ],
            'essays' => $examAttempt->answers
                ->filter(fn (ExamAnswer $answer): bool => $answer->question?->type === QuestionType::Esai
                    && $answer->answer !== null
                    && trim($answer->answer) !== '')
                ->map(fn (ExamAnswer $answer): array => [
                    'id' => $answer->id,
                    'question_no' => $answer->question_no,
                    'question' => $answer->question?->body,
                    'answer' => ExamAnswer::restoreAnswer($answer->answer),
                    'max_score' => (float) ($answer->question?->score ?? 0),
                    'score_awarded' => $answer->score_awarded === null
                        ? null
                        : (float) $answer->score_awarded,
                ])
                ->values()
                ->all(),
        ]);
    }

    public function update(
        GradeExamEssayRequest $request,
        ExamAttempt $examAttempt,
        ExamAnswer $examAnswer,
        GradeExamAttemptService $grading,
    ): RedirectResponse {
        $this->authorizeAttemptSchool($request, $examAttempt);

        abort_unless((int) $examAnswer->exam_attempt_id === (int) $examAttempt->id, 404);
        $examAnswer->loadMissing('question');
        abort_unless($examAnswer->question?->type === QuestionType::Esai, 404);

        $scoreAwarded = (float) $request->validated('score_awarded');
        $maximumScore = (float) $examAnswer->question->score;

        if ($scoreAwarded > $maximumScore) {
            return back()->withErrors([
                'score_awarded' => "Nilai tidak boleh melebihi {$maximumScore}.",
            ]);
        }

        $examAnswer->forceFill([
            'score_awarded' => $scoreAwarded,
            'is_correct' => $scoreAwarded >= $maximumScore,
        ])->save();

        $grading->refreshAttemptTotals($examAttempt, (int) $request->user()->id);

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Nilai Esai Disimpan',
            'description' => 'Nilai jawaban esai berhasil diperbarui.',
        ]);
    }

    private function authorizeAttemptSchool(Request $request, ExamAttempt $examAttempt): void
    {
        $examAttempt->loadMissing('examPackage');

        abort_unless(
            in_array((int) $examAttempt->examPackage?->school_id, $request->user()->accessibleSchoolIds(), true),
            404,
        );
    }
}
