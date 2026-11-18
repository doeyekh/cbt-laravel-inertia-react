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
use Illuminate\Support\Facades\DB;

class ExamSubmitController extends Controller
{
    /**
     * Finalize the student's attempt so its answers can no longer change.
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

        $verifiedExamPackages = $request->session()->get('student_exam_packages', []);
        $storedHash = is_array($verifiedExamPackages)
            ? ($verifiedExamPackages[$examPackage->id] ?? null)
            : null;
        $currentHash = $examPackage->exam_access_token_hash;

        if (! is_string($storedHash) || ! is_string($currentHash) || ! hash_equals($currentHash, $storedHash)) {
            return response()->json(['message' => 'Token ujian belum diverifikasi.'], 403);
        }

        if (! $examAccess->isActive($examPackage, $examSchedule)) {
            return response()->json(['message' => 'Paket atau jadwal ujian sudah tidak aktif.'], 403);
        }

        $attempt = ExamAttempt::query()
            ->where('exam_participant_id', $participant->id)
            ->where('exam_schedule_id', $examSchedule->id)
            ->first();

        if ($attempt === null || ! $request->has('attempt_id')) {
            return response()->json([
                'message' => 'Attempt telah direset. Muat ulang halaman ujian untuk memulai kembali.',
                'status' => 'reset',
            ], 409);
        }

        $request->validate([
            'attempt_id' => ['required', 'integer', 'min:1'],
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
        ]);

        if ($request->integer('attempt_id') !== (int) $attempt->id) {
            return response()->json([
                'message' => 'Attempt telah direset. Muat ulang halaman ujian untuk memulai kembali.',
                'status' => 'reset',
            ], 409);
        }

        if (! $attempt->isSubmitted()) {
            DB::transaction(function () use ($attempt, $examAccess, $examPackage, $examSchedule, $request, $grading): void {
                $questionBank = $examAccess->resolveQuestionBank($examSchedule, $examPackage);
                $questionNumbers = $questionBank?->questions->pluck('number', 'id') ?? collect();
                $validQuestionIds = $questionBank?->questions
                    ->where('is_active', true)
                    ->pluck('id')
                    ->toArray() ?? [];

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

                $attempt->refresh();
                $attempt->submit();
                $grading->grade($attempt);
            });
        }

        $attempt->refresh();

        return response()->json([
            'message' => 'Ujian berhasil dikumpulkan.',
            'attempt_id' => $attempt->id,
            'status' => $attempt->status->value,
            'submitted_at' => $attempt->submitted_at?->toIso8601String(),
            'time_spent_seconds' => $attempt->time_spent_seconds,
            'server_now' => now()->toIso8601String(),
        ]);
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
