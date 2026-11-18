<?php

namespace App\Http\Controllers\Student;

use App\Enums\ExamAttemptStatus;
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
use Illuminate\Support\Collection;
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
            return to_route('student.exam-result.show', $examSchedule)->with('toast', [
                'type' => 'info',
                'title' => 'Ujian Sudah Dikumpulkan',
                'description' => 'Jawaban Anda sudah final dan tidak dapat diubah lagi.',
            ]);
        }

        $questionBank = $examAccess->resolveQuestionBank($examSchedule, $examPackage);

        if (! $questionBank) {
            abort(404);
        }

        if ($attempt === null) {
            abort_unless($examAccess->scheduleIsOpen($examPackage, $examSchedule), 403);

            $startedAt = now();
            $attempt = ExamAttempt::query()->createOrFirst(
                [
                    'exam_participant_id' => $participant->id,
                    'exam_schedule_id' => $examSchedule->id,
                ],
                [
                    'exam_package_id' => $examPackage->id,
                    'status' => ExamAttemptStatus::InProgress,
                    'duration_minutes' => $examSchedule->duration_minutes,
                    'time_spent_seconds' => 0,
                    'started_at' => $startedAt,
                    'deadline_at' => $examAccess->deadlineFor($examSchedule, $startedAt),
                ],
            );
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

        $this->ensureShuffleOrder($attempt, $questions, $examSchedule);
        $questionOrder = array_map('intval', $attempt->question_order ?? []);
        $questions = $questions
            ->sortBy(fn (Question $question): int|false => array_search((int) $question->id, $questionOrder, true))
            ->values();

        foreach ($questions as $question) {
            $optionOrder = $attempt->option_order[(string) $question->id] ?? [];
            $options = $question->options->keyBy('id');
            $orderedOptions = collect($optionOrder)
                ->map(fn (int|string $optionId) => $options->get((int) $optionId))
                ->filter()
                ->concat($question->options->reject(
                    fn ($option): bool => in_array((int) $option->id, array_map('intval', $optionOrder), true),
                ))
                ->values();
            $question->setRelation('options', $orderedOptions);
        }

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
            'questions' => $this->presentShuffledQuestions($questions, $attempt, $examSchedule),
            'answers' => $answers,
            'flags' => $flags,
            'remaining_seconds' => $remainingSeconds,
            'duration_seconds' => $durationSeconds,
            'deadline_at' => $attempt?->resolveDeadline()->toIso8601String(),
            'server_now' => now()->toIso8601String(),
        ]);
    }

    /**
     * Persist a participant-specific question and option order for stable reloads.
     *
     * @param  Collection<int, Question>  $questions
     */
    private function ensureShuffleOrder(
        ExamAttempt $attempt,
        Collection $questions,
        ExamSchedule $examSchedule,
    ): void {
        $questionIds = $questions->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $questionOrder = $attempt->question_order;

        if (! is_array($questionOrder)) {
            $questionOrder = $questionIds;

            if ($examSchedule->shuffle_questions) {
                shuffle($questionOrder);
            }
        } else {
            $questionOrder = array_values(array_filter(
                array_map('intval', $questionOrder),
                fn (int $id): bool => in_array($id, $questionIds, true),
            ));

            foreach ($questionIds as $questionId) {
                if (! in_array($questionId, $questionOrder, true)) {
                    $questionOrder[] = $questionId;
                }
            }
        }

        $optionOrder = is_array($attempt->option_order) ? $attempt->option_order : [];

        foreach ($questions as $question) {
            $questionId = (string) $question->id;

            if (isset($optionOrder[$questionId]) && is_array($optionOrder[$questionId])) {
                continue;
            }

            $optionIds = $question->type->usesPairs()
                ? $question->pairs->sortBy('order')->pluck('id')->map(fn ($id): int => (int) $id)->all()
                : $question->options->sortBy('order')->pluck('id')->map(fn ($id): int => (int) $id)->all();

            if ($examSchedule->shuffle_options) {
                shuffle($optionIds);
            }

            $optionOrder[$questionId] = $optionIds;
        }

        if ($attempt->question_order !== $questionOrder || $attempt->option_order !== $optionOrder) {
            $attempt->forceFill([
                'question_order' => $questionOrder,
                'option_order' => $optionOrder,
            ])->save();
        }
    }

    /**
     * Present options and matching targets in the persisted per-attempt order.
     *
     * @param  Collection<int, Question>  $questions
     * @return array<int, array<string, mixed>>
     */
    private function presentShuffledQuestions(
        Collection $questions,
        ExamAttempt $attempt,
        ExamSchedule $examSchedule,
    ): array {
        $presentedQuestions = $this->presentExamQuestions($questions);

        foreach ($questions->values() as $index => $question) {
            $optionOrder = array_map(
                'intval',
                $attempt->option_order[(string) $question->id] ?? [],
            );

            if ($question->type->usesPairs()) {
                $pairsById = $question->pairs->keyBy('id');
                $presentedQuestions[$index]['right_pairs'] = collect($optionOrder)
                    ->map(fn (int $pairId) => $pairsById->get($pairId))
                    ->filter()
                    ->map(fn ($pair): array => [
                        'id' => $pair->id,
                        'right_text' => $pair->right_text,
                    ])
                    ->values()
                    ->all();

                continue;
            }

            if (! $examSchedule->shuffle_options) {
                continue;
            }

            foreach ($presentedQuestions[$index]['options'] as $optionIndex => &$option) {
                $option['label'] = chr(65 + $optionIndex);
            }
            unset($option);
        }

        return $presentedQuestions;
    }
}
