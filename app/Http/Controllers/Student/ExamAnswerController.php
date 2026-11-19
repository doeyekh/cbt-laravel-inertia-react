<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\ExamParticipant;
use App\Models\ExamSchedule;
use App\Services\ExamScheduleAccessService;
use App\Services\GradeExamAttemptService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ExamAnswerController extends Controller
{
    /**
     * Store or update the student's answers for an exam schedule.
     */
    public function store(
        Request $request,
        ExamSchedule $examSchedule,
        ExamScheduleAccessService $examAccess,
        GradeExamAttemptService $grading,
    ): JsonResponse {
        $participant = ExamParticipant::query()
            ->findOrFail((int) $request->session()->get('student_participant_id'));

        $examSchedule->loadMissing('examPackage');
        $examPackage = $examSchedule->examPackage;

        if ($examPackage === null || ! $examAccess->participantMayAccess($participant, $examSchedule, $examPackage)) {
            return response()->json(['message' => 'Akses tidak diizinkan untuk jadwal ujian ini.'], 403);
        }

        if (! $examAccess->isActive($examPackage, $examSchedule)) {
            return response()->json(['message' => 'Paket atau jadwal ujian sudah tidak aktif.'], 403);
        }

        $verifiedExamPackages = $request->session()->get('student_exam_packages', []);
        $storedHash = is_array($verifiedExamPackages)
            ? ($verifiedExamPackages[$examPackage->id] ?? null)
            : null;
        $currentHash = $examPackage->exam_access_token_hash;

        if (! is_string($storedHash) || ! is_string($currentHash) || ! hash_equals($currentHash, $storedHash)) {
            return response()->json(['message' => 'Token ujian belum diverifikasi.'], 403);
        }

        $attempt = ExamAttempt::query()
            ->where('exam_participant_id', $participant->id)
            ->where('exam_schedule_id', $examSchedule->id)
            ->first();

        if ($attempt === null && ! $examAccess->scheduleIsOpen($examPackage, $examSchedule)) {
            return response()->json(['message' => 'Jadwal ujian belum dibuka atau sudah ditutup.'], 403);
        }

        if ($attempt === null || ! $request->has('attempt_id')) {
            return response()->json([
                'message' => 'Attempt telah direset. Muat ulang halaman ujian untuk memulai kembali.',
                'status' => 'reset',
            ], 409);
        }

        $request->validate([
            'attempt_id' => ['required', 'integer', 'min:1'],
        ]);

        if ($request->integer('attempt_id') !== (int) $attempt->id) {
            return response()->json([
                'message' => 'Attempt telah direset. Muat ulang halaman ujian untuk memulai kembali.',
                'status' => 'reset',
            ], 409);
        }

        if ($attempt->isSubmitted()) {
            return response()->json([
                'message' => 'Ujian sudah dikumpulkan. Jawaban tidak dapat diubah lagi.',
                'status' => 'submitted',
                'submitted_at' => $attempt->submitted_at?->toIso8601String(),
                'server_now' => now()->toIso8601String(),
            ], 409);
        }

        if ($attempt->hasReachedDeadline()) {
            $attempt->submit();
            $grading->grade($attempt);

            return response()->json([
                'message' => 'Waktu ujian telah habis. Jawaban tidak dapat diubah lagi.',
                'status' => 'expired',
                'attempt_status' => $attempt->status->value,
                'deadline_at' => $attempt->resolveDeadline()->toIso8601String(),
                'remaining_seconds' => 0,
                'server_now' => now()->toIso8601String(),
            ], 409);
        }

        $request->validate([
            'answers' => 'sometimes|array',
            'answers.*' => [
                'nullable',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (is_string($value)) {
                        return;
                    }

                    if (
                        is_array($value)
                        && array_is_list($value)
                        && collect($value)->every(fn (mixed $answer): bool => is_string($answer))
                    ) {
                        return;
                    }

                    $fail('The answer must be a string or a list of strings.');
                },
            ],
            'flags' => 'sometimes|array',
            'flags.*' => 'integer',
        ]);

        $questionBank = $examAccess->resolveQuestionBank($examSchedule, $examPackage);
        $questionNumbers = $questionBank?->questions->pluck('number', 'id') ?? collect();
        $validQuestionIds = $questionBank?->questions
            ->where('is_active', true)
            ->pluck('id')
            ->toArray() ?? [];

        $attempt->update(['time_spent_seconds' => $attempt->elapsedSeconds()]);

        foreach ($request->array('answers') as $questionId => $answer) {
            $questionId = (int) $questionId;

            if (! in_array($questionId, $validQuestionIds, true)) {
                continue;
            }

            if (! $this->hasAnswerContent($answer)) {
                ExamAnswer::query()
                    ->where('exam_attempt_id', $attempt->id)
                    ->where('question_id', $questionId)
                    ->delete();

                continue;
            }

            ExamAnswer::updateOrCreate(
                [
                    'exam_attempt_id' => $attempt->id,
                    'question_id' => $questionId,
                ],
                [
                    'question_no' => (int) ($questionNumbers[$questionId] ?? 0),
                    'answer' => ExamAnswer::serializeAnswer($answer),
                ]
            );
        }

        if ($request->has('flags')) {
            $flaggedQuestionIds = collect($request->array('flags'))
                ->map(fn (mixed $questionId): int => (int) $questionId)
                ->unique()
                ->intersect($validQuestionIds)
                ->values()
                ->all();

            $this->syncFlags($attempt, $flaggedQuestionIds, $questionNumbers);
        }

        return response()->json([
            'message' => 'Jawaban berhasil disimpan.',
            'attempt_id' => $attempt->id,
            'status' => 'in_progress',
            'deadline_at' => $attempt->resolveDeadline()->toIso8601String(),
            'remaining_seconds' => $attempt->remainingSeconds(),
            'server_now' => now()->toIso8601String(),
            'flags' => $this->flaggedQuestionIds($attempt),
        ]);
    }

    /**
     * Persist the ragu-ragu markers without touching the stored answers.
     *
     * @param  array<int, int>  $flaggedQuestionIds
     * @param  Collection<int, int>  $questionNumbers
     */
    private function syncFlags(ExamAttempt $attempt, array $flaggedQuestionIds, Collection $questionNumbers): void
    {
        foreach ($flaggedQuestionIds as $questionId) {
            $examAnswer = ExamAnswer::query()->firstOrCreate(
                [
                    'exam_attempt_id' => $attempt->id,
                    'question_id' => $questionId,
                ],
                [
                    'question_no' => (int) ($questionNumbers[$questionId] ?? 0),
                    'answer' => null,
                ]
            );

            if (! $examAnswer->is_flagged) {
                $examAnswer->update(['is_flagged' => true]);
            }
        }

        ExamAnswer::query()
            ->where('exam_attempt_id', $attempt->id)
            ->where('is_flagged', true)
            ->whereNotIn('question_id', $flaggedQuestionIds)
            ->update(['is_flagged' => false]);
    }

    /**
     * Get the question IDs currently marked as ragu-ragu.
     *
     * @return array<int, int>
     */
    private function flaggedQuestionIds(ExamAttempt $attempt): array
    {
        return ExamAnswer::query()
            ->where('exam_attempt_id', $attempt->id)
            ->where('is_flagged', true)
            ->pluck('question_id')
            ->map(fn (mixed $questionId): int => (int) $questionId)
            ->all();
    }

    /**
     * Determine whether an answer payload contains a usable value.
     */
    private function hasAnswerContent(string|array|null $answer): bool
    {
        if ($answer === null || $answer === 'flagged') {
            return false;
        }

        if (is_string($answer)) {
            return $answer !== '';
        }

        return collect($answer)
            ->filter(fn (mixed $value): bool => is_string($value) && $value !== '')
            ->isNotEmpty();
    }
}
