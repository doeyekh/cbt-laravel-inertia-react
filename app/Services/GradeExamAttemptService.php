<?php

namespace App\Services;

use App\Enums\ExamAttemptStatus;
use App\Enums\QuestionType;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\QuestionBlank;
use App\Models\QuestionOption;

class GradeExamAttemptService
{
    public function __construct(
        private ExamScheduleAccessService $examAccess,
    ) {}

    /**
     * Grade all automatically gradable questions in a submitted attempt.
     */
    public function grade(ExamAttempt $attempt): void
    {
        $attempt->loadMissing(['examSchedule.examPackage', 'examSchedule.questionBank', 'answers']);
        $examSchedule = $attempt->examSchedule;
        $questionBank = $this->examAccess->resolveQuestionBank($examSchedule, $examSchedule->examPackage);

        if ($questionBank === null) {
            abort(409, 'Bank soal untuk jadwal ini tidak dapat ditentukan.');
        }

        $questions = Question::query()
            ->where('question_bank_id', $questionBank->id)
            ->where('is_active', true)
            ->with(['options', 'pairs', 'blanks'])
            ->orderBy('number')
            ->get();

        foreach ($questions as $question) {
            $examAnswer = $attempt->answers->firstWhere('question_id', $question->id);
            $examAnswer ??= ExamAnswer::query()->firstOrCreate(
                [
                    'exam_attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                ],
                ['question_no' => $question->number],
            );

            [$isCorrect, $scoreAwarded] = $this->gradeQuestion(
                $question,
                ExamAnswer::restoreAnswer($examAnswer->answer),
            );

            $examAnswer->forceFill([
                'question_no' => $question->number,
                'is_correct' => $isCorrect,
                'score_awarded' => $scoreAwarded,
            ])->save();
        }

        $this->refreshAttemptTotals($attempt);
    }

    /**
     * Recalculate attempt totals after a manual essay grade.
     */
    public function refreshAttemptTotals(ExamAttempt $attempt, ?int $gradedBy = null): void
    {
        $attempt->unsetRelation('answers');
        $attempt->loadMissing(['answers.question']);
        $hasUngradedEssay = $attempt->answers->contains(
            fn (ExamAnswer $answer): bool => $answer->question?->type === QuestionType::Esai
                && self::hasAnswerContent(ExamAnswer::restoreAnswer($answer->answer))
                && $answer->score_awarded === null,
        );
        $score = (float) $attempt->answers->sum(
            fn (ExamAnswer $answer): float => (float) ($answer->score_awarded ?? 0),
        );
        $maxScore = (float) $attempt->answers->sum(
            fn (ExamAnswer $answer): float => (float) ($answer->question?->score ?? 0),
        );

        $attempt->forceFill([
            'score' => round($score, 2),
            'max_score' => round($maxScore, 2),
            'status' => $hasUngradedEssay ? ExamAttemptStatus::Submitted : ExamAttemptStatus::Graded,
            'graded_at' => $hasUngradedEssay ? null : ($attempt->graded_at ?? now()),
            'graded_by' => $hasUngradedEssay ? null : $gradedBy,
        ])->save();
    }

    /**
     * Grade a single question and return correctness and awarded score.
     *
     * @return array{bool|null, float|null}
     */
    private function gradeQuestion(Question $question, string|array|null $answer): array
    {
        $maximumScore = (float) $question->score;

        if ($question->type === QuestionType::Esai) {
            return self::hasAnswerContent($answer)
                ? [null, null]
                : [false, 0.0];
        }

        if (! self::hasAnswerContent($answer)) {
            return [false, 0.0];
        }

        if ($question->type === QuestionType::PilihanGanda) {
            $option = $this->resolveOption($question, (string) $answer);
            $isCorrect = $option?->is_correct === true;

            return [$isCorrect, $isCorrect ? $maximumScore : 0.0];
        }

        if ($question->type === QuestionType::PilihanGandaKompleks) {
            $selectedIds = collect(is_array($answer) ? $answer : [$answer])
                ->map(fn (mixed $value): ?int => $this->resolveOption($question, (string) $value)?->id)
                ->filter()
                ->unique()
                ->sort()
                ->values();
            $correctIds = $question->options
                ->where('is_correct', true)
                ->pluck('id')
                ->sort()
                ->values();
            $isCorrect = $selectedIds->all() === $correctIds->all();

            return [$isCorrect, $isCorrect ? $maximumScore : 0.0];
        }

        if ($question->type === QuestionType::Menjodohkan) {
            $pairs = $question->pairs->sortBy('order')->values();
            $matches = 0;

            foreach ($pairs as $index => $pair) {
                if ((string) ($answer[$index] ?? '') === (string) $pair->id) {
                    $matches++;
                }
            }

            $pairCount = $pairs->count();
            $isCorrect = $pairCount > 0 && $matches === $pairCount;
            $scoreAwarded = $pairCount > 0
                ? round($maximumScore * $matches / $pairCount, 2)
                : 0.0;

            return [$isCorrect, $scoreAwarded];
        }

        if ($question->type === QuestionType::IsianSingkat) {
            $blanks = $question->blanks->sortBy('order')->values();
            $answers = is_array($answer) ? $answer : [$answer];
            $maximumBlankScore = $blanks->sum(
                fn (QuestionBlank $blank): float => (float) ($blank->score ?? 0),
            );
            $scoreAwarded = 0.0;
            $correctBlankCount = 0;

            foreach ($blanks as $index => $blank) {
                $studentAnswer = self::normalizeText((string) ($answers[$index] ?? ''));
                $acceptedAnswers = collect($blank->answers ?? [])
                    ->map(fn (mixed $accepted): string => self::normalizeText((string) $accepted));
                $isBlankCorrect = $studentAnswer !== '' && $acceptedAnswers->contains($studentAnswer);

                if ($isBlankCorrect) {
                    $correctBlankCount++;
                    $blankScore = (float) ($blank->score ?? 0);
                    $scoreAwarded += $blankScore > 0
                        ? $blankScore
                        : ($blanks->isNotEmpty() ? $maximumScore / $blanks->count() : 0);
                }
            }

            if ($maximumBlankScore > 0 && $scoreAwarded > $maximumBlankScore) {
                $scoreAwarded = $maximumScore * ($scoreAwarded / $maximumBlankScore);
            }

            $isCorrect = $blanks->isNotEmpty() && $correctBlankCount === $blanks->count();

            return [$isCorrect, min($maximumScore, round($scoreAwarded, 2))];
        }

        return [false, 0.0];
    }

    private function resolveOption(Question $question, string $answer): ?QuestionOption
    {
        $option = $question->options->first(
            fn (QuestionOption $option): bool => (string) $option->id === $answer,
        );

        if ($option !== null) {
            return $option;
        }

        return $question->options->first(
            fn (QuestionOption $option): bool => $option->label === $answer || $option->text === $answer,
        );
    }

    public static function normalizeText(string $value): string
    {
        $normalized = mb_strtolower(trim($value));

        return preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;
    }

    public static function hasAnswerContent(string|array|null $answer): bool
    {
        if ($answer === null || $answer === '') {
            return false;
        }

        if (is_string($answer)) {
            return trim($answer) !== '';
        }

        return collect($answer)->contains(
            fn (mixed $value): bool => is_string($value) && trim($value) !== '',
        );
    }
}
