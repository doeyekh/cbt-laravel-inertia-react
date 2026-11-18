<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ExamAttemptStatus;
use App\Http\Controllers\Controller;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\ExamParticipant;
use App\Models\ExamSchedule;
use App\Models\Question;
use App\Services\ExamScheduleAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function dashboard(): Response
    {
        return Inertia::render('Home', [
            'title' => 'Beranda - CBT Panel',
        ]);
    }

    public function monitoring(Request $request, ExamScheduleAccessService $examAccess): Response
    {
        $now = now();
        $schoolIds = $request->user()->accessibleSchoolIds();
        $schedules = ExamSchedule::query()
            ->where('status', 'aktif')
            ->whereDate('exam_date', $now->toDateString())
            ->whereTime('open_time', '<=', $now->format('H:i:s'))
            ->whereTime('close_time', '>=', $now->format('H:i:s'))
            ->whereHas('examPackage', fn ($query) => $query
                ->where('status', 'aktif')
                ->whereIn('school_id', $schoolIds))
            ->with(['examPackage', 'schoolClass', 'subject'])
            ->get();

        $participants = [];

        foreach ($schedules as $examSchedule) {
            $examPackage = $examSchedule->examPackage;
            $questionBank = $examAccess->resolveQuestionBank($examSchedule, $examPackage);
            $totalQuestions = $questionBank === null
                ? 0
                : Question::query()
                    ->where('question_bank_id', $questionBank->id)
                    ->where('is_active', true)
                    ->count();
            $scheduledParticipants = ExamParticipant::query()
                ->where('school_id', $examPackage->school_id)
                ->where('academic_year_id', $examPackage->academic_year_id)
                ->when($examSchedule->session_id !== null, fn ($query) => $query
                    ->where('session_id', $examSchedule->session_id))
                ->when($examSchedule->room_id !== null, fn ($query) => $query
                    ->where('room_id', $examSchedule->room_id))
                ->whereHas('student.classMembers', fn ($query) => $query
                    ->where('class_id', $examSchedule->class_id)
                    ->where('academic_year_id', $examPackage->academic_year_id))
                ->with(['student', 'room'])
                ->get();
            $attempts = ExamAttempt::query()
                ->where('exam_schedule_id', $examSchedule->id)
                ->whereIn('exam_participant_id', $scheduledParticipants->pluck('id'))
                ->with(['answers.question'])
                ->get()
                ->keyBy('exam_participant_id');

            foreach ($scheduledParticipants as $participant) {
                $participants[] = $this->presentMonitoringParticipant(
                    $examSchedule,
                    $participant,
                    $attempts->get($participant->id),
                    $totalQuestions,
                    $now,
                );
            }
        }

        return Inertia::render('Monitoring/Index', [
            'title' => 'Monitoring Ujian',
            'participants' => $participants,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function presentMonitoringParticipant(
        ExamSchedule $examSchedule,
        ExamParticipant $participant,
        ?ExamAttempt $attempt,
        int $totalQuestions,
        Carbon $now,
    ): array {
        $answeredCount = $attempt?->answers
            ->filter(fn (ExamAnswer $answer): bool => $answer->answer !== null
                && $answer->answer !== ''
                && $answer->answer !== 'flagged')
            ->count() ?? 0;
        $isOnline = $attempt?->updated_at?->gte($now->copy()->subMinutes(2)) ?? false;
        $pendingEssayCount = $attempt?->answers
            ->filter(fn (ExamAnswer $answer): bool => $answer->question?->type?->value === 'esai'
                && $answer->answer !== null
                && trim($answer->answer) !== ''
                && $answer->score_awarded === null)
            ->count() ?? 0;

        return [
            'id' => "{$examSchedule->id}-{$participant->id}",
            'attempt_id' => $attempt?->id,
            'no' => (string) $participant->participant_number,
            'name' => $participant->student?->name ?? 'Peserta Ujian',
            'nisn' => $participant->student?->nisn ?? '-',
            'class' => $examSchedule->schoolClass?->name ?? '-',
            'room' => $participant->room?->name ?? '-',
            'subject' => $examSchedule->subject?->name ?? '-',
            'progress' => [
                'current' => $answeredCount,
                'total' => $totalQuestions,
                'percent' => $totalQuestions > 0
                    ? (int) round($answeredCount / $totalQuestions * 100)
                    : 0,
            ],
            'connection' => $attempt === null
                ? 'not_started'
                : ($isOnline ? 'stable' : 'disconnected'),
            'last_activity' => $attempt?->updated_at?->toIso8601String(),
            'time_spent_seconds' => $attempt?->time_spent_seconds ?? 0,
            'flagged_count' => $attempt?->answers->where('is_flagged', true)->count() ?? 0,
            'status' => $attempt === null
                ? 'not_started'
                : ($attempt->status === ExamAttemptStatus::InProgress
                    ? ($isOnline ? 'active' : 'disconnected')
                    : 'submitted'),
            'pending_essay_count' => $pendingEssayCount,
            'grade_url' => $pendingEssayCount > 0
                ? route('admin.exam-attempts.essays.index', $attempt)
                : null,
        ];
    }
}
