<?php

namespace App\Http\Controllers\Student;

use App\Enums\ExamAttemptStatus;
use App\Http\Controllers\Controller;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\ExamParticipant;
use App\Models\ExamSchedule;
use App\Services\ExamScheduleAccessService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExamSubmitController extends Controller
{
    /**
     * Finalize the student's attempt so its answers can no longer change.
     */
    public function store(
        Request $request,
        ExamSchedule $examSchedule,
        ExamScheduleAccessService $examAccess,
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
        ]);

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
            ]
        );

        if (! $attempt->isSubmitted()) {
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

            $attempt->submit();
        }

        return response()->json([
            'message' => 'Ujian berhasil dikumpulkan.',
            'attempt_id' => $attempt->id,
            'status' => 'submitted',
            'submitted_at' => $attempt->submitted_at?->toIso8601String(),
            'time_spent_seconds' => $attempt->time_spent_seconds,
            'server_now' => now()->toIso8601String(),
        ]);
    }
}
