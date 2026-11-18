<?php

namespace App\Http\Controllers\Student;

use App\Enums\ExamAttemptStatus;
use App\Enums\QuestionType;
use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;
use App\Models\ExamParticipant;
use App\Models\ExamSchedule;
use App\Services\ExamScheduleAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExamResultController extends Controller
{
    public function show(
        Request $request,
        ExamSchedule $examSchedule,
        ExamScheduleAccessService $examAccess,
    ): Response|RedirectResponse {
        $participant = ExamParticipant::query()
            ->findOrFail((int) $request->session()->get('student_participant_id'));
        $examSchedule->loadMissing(['examPackage.examType', 'subject']);
        $examPackage = $examSchedule->examPackage;

        if ($examPackage === null || ! $examAccess->participantMayAccess($participant, $examSchedule, $examPackage)) {
            abort(404);
        }

        $attempt = ExamAttempt::query()
            ->where('exam_participant_id', $participant->id)
            ->where('exam_schedule_id', $examSchedule->id)
            ->with('answers.question')
            ->firstOrFail();

        if ($attempt->status === ExamAttemptStatus::InProgress) {
            return to_route('student.exam', $examSchedule);
        }

        $canViewScore = (bool) $examSchedule->show_score_to_student
            && $attempt->status === ExamAttemptStatus::Graded;

        return Inertia::render('Student/ExamResult', [
            'exam' => [
                'id' => $examSchedule->id,
                'subject' => $examSchedule->subject?->name,
                'package' => $examPackage->name,
                'exam_type' => $examPackage->examType?->name,
                'submitted_at' => $attempt->submitted_at?->toIso8601String(),
            ],
            'status' => $attempt->status->value,
            'score_visible' => $canViewScore,
            'score' => $canViewScore ? (float) $attempt->score : null,
            'max_score' => $canViewScore ? (float) $attempt->max_score : null,
            'correct_count' => $canViewScore
                ? $attempt->answers->where('is_correct', true)->count()
                : null,
            'total_questions' => $canViewScore ? $attempt->answers->count() : null,
            'pending_essay_count' => $attempt->answers
                ->filter(fn ($answer): bool => $answer->question?->type === QuestionType::Esai
                    && $answer->answer !== null
                    && $answer->score_awarded === null)
                ->count(),
        ]);
    }
}
