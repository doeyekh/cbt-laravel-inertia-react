<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ExamAttemptStatus;
use App\Exports\ExamReportExport;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\ExamSchedule;
use App\Models\Question;
use App\Models\SchoolClass;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExamReportController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $schoolIds = $request->user()->accessibleSchoolIds();

        $request->validate([
            'academic_year_id' => ['nullable', 'integer'],
            'package_id' => ['nullable', 'integer'],
            'subject_id' => ['nullable', 'integer'],
            'class_id' => ['nullable', 'integer'],
        ]);

        $schedulesQuery = ExamSchedule::query()
            ->with([
                'examPackage:id,name,code,school_id,academic_year_id,exam_type_id',
                'examPackage.academicYear:id,name',
                'subject:id,name',
                'schoolClass:id,name',
                'session:id,name',
                'room:id,name',
            ])
            ->whereHas('examPackage', fn ($query) => $query->whereIn('school_id', $schoolIds))
            ->when($request->integer('academic_year_id') > 0, fn ($query) => $query
                ->whereHas('examPackage', fn ($packageQuery) => $packageQuery
                    ->where('academic_year_id', $request->integer('academic_year_id'))))
            ->when($request->integer('package_id') > 0, fn ($query) => $query
                ->where('exam_package_id', $request->integer('package_id')))
            ->when($request->integer('subject_id') > 0, fn ($query) => $query
                ->where('subject_id', $request->integer('subject_id')))
            ->when($request->integer('class_id') > 0, fn ($query) => $query
                ->where('class_id', $request->integer('class_id')))
            ->orderByDesc('exam_date')
            ->orderByDesc('open_time');

        $schedules = $schedulesQuery->get();
        $scheduleIds = $schedules->pluck('id');
        $attempts = $scheduleIds->isEmpty()
            ? collect()
            : ExamAttempt::query()
                ->whereIn('exam_schedule_id', $scheduleIds)
                ->with(['participant.student'])
                ->get();

        $completedAttempts = $attempts->filter(
            fn (ExamAttempt $attempt): bool => $attempt->status !== ExamAttemptStatus::InProgress,
        );
        $scoredAttempts = $completedAttempts->filter(
            fn (ExamAttempt $attempt): bool => $attempt->score !== null,
        );
        $averages = $scoredAttempts
            ->map(fn (ExamAttempt $attempt): float => $attempt->scorePercentage() ?? 0)
            ->values();

        $reportRows = $schedules->map(function (ExamSchedule $schedule) use ($attempts): array {
            $scheduleAttempts = $attempts->where('exam_schedule_id', $schedule->id);
            $completed = $scheduleAttempts->filter(
                fn (ExamAttempt $attempt): bool => $attempt->status !== ExamAttemptStatus::InProgress,
            );
            $scored = $completed->filter(fn (ExamAttempt $attempt): bool => $attempt->score !== null);
            $percentages = $scored
                ->map(fn (ExamAttempt $attempt): float => $attempt->scorePercentage() ?? 0);
            $passed = $percentages->filter(fn (float $percentage): bool => $percentage >= 70)->count();

            return [
                'id' => $schedule->id,
                'name' => $schedule->examPackage?->name,
                'code' => $schedule->examPackage?->code,
                'subject' => $schedule->subject?->name,
                'class' => $schedule->schoolClass?->name,
                'session' => $schedule->session?->name,
                'room' => $schedule->room?->name,
                'exam_date' => $schedule->exam_date?->format('Y-m-d'),
                'open_time' => $schedule->open_time,
                'close_time' => $schedule->close_time,
                'participant_count' => $scheduleAttempts->count(),
                'completed_count' => $completed->count(),
                'average_percentage' => $percentages->count() > 0
                    ? round($percentages->average(), 2)
                    : null,
                'passed_count' => $passed,
            ];
        });

        $academicYearIds = ExamPackage::query()
            ->whereIn('school_id', $schoolIds)
            ->distinct()
            ->pluck('academic_year_id');
        $subjectIds = ExamSchedule::query()
            ->whereHas('examPackage', fn ($query) => $query->whereIn('school_id', $schoolIds))
            ->distinct()
            ->pluck('subject_id');
        $packages = ExamPackage::query()
            ->whereIn('school_id', $schoolIds)
            ->when($request->integer('academic_year_id') > 0, fn ($query) => $query
                ->where('academic_year_id', $request->integer('academic_year_id')))
            ->orderByDesc('start_date')
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn (ExamPackage $package): array => [
                'id' => $package->id,
                'name' => $package->name,
                'code' => $package->code,
            ]);

        return Inertia::render('Reports/Index', [
            'title' => 'Laporan Ujian',
            'filters' => [
                'academic_year_id' => $request->input('academic_year_id'),
                'package_id' => $request->input('package_id'),
                'subject_id' => $request->input('subject_id'),
                'class_id' => $request->input('class_id'),
            ],
            'academicYears' => AcademicYear::query()
                ->whereIn('id', $academicYearIds)
                ->orderByDesc('name')
                ->get(['id', 'name'])
                ->map(fn (AcademicYear $year): array => ['id' => $year->id, 'name' => $year->name]),
            'subjects' => Subject::query()
                ->whereIn('id', $subjectIds)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Subject $subject): array => ['id' => $subject->id, 'name' => $subject->name]),
            'packages' => $packages,
            'classes' => SchoolClass::query()
                ->whereIn('school_id', $schoolIds)
                ->when($request->integer('academic_year_id') > 0, fn ($query) => $query
                    ->where('academic_year_id', $request->integer('academic_year_id')))
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (SchoolClass $class): array => ['id' => $class->id, 'name' => $class->name]),
            'reports' => $reportRows,
            'summary' => [
                'schedule_count' => $schedules->count(),
                'participant_count' => $attempts->unique('exam_participant_id')->count(),
                'completed_count' => $completedAttempts->count(),
                'average_percentage' => $averages->count() > 0
                    ? round($averages->average(), 2)
                    : null,
                'pass_percentage' => $averages->count() > 0
                    ? round($averages->filter(fn (float $value): bool => $value >= 70)->count() / $averages->count() * 100, 2)
                    : null,
            ],
        ]);
    }

    public function show(Request $request, ExamSchedule $examSchedule): InertiaResponse
    {
        $this->authorizeSchedule($request, $examSchedule);
        $examSchedule->load([
            'examPackage:id,name,code,school_id,academic_year_id',
            'subject:id,name',
            'schoolClass:id,name',
            'session:id,name',
            'room:id,name',
        ]);

        $attempts = ExamAttempt::query()
            ->where('exam_schedule_id', $examSchedule->id)
            ->with(['participant.student', 'answers.question'])
            ->orderByDesc('submitted_at')
            ->get();

        $bank = $examSchedule->question_bank_id !== null
            ? $examSchedule->questionBank()->with(['questions' => fn ($query) => $query
                ->where('is_active', true)
                ->orderBy('number')
                ->with(['options'])])->first()
            : null;

        $questions = $bank?->questions ?? collect();
        $questionAnalysis = $questions->map(function (Question $question) use ($attempts): array {
            $answers = $attempts->flatMap(
                fn (ExamAttempt $attempt): iterable => $attempt->answers
                    ->where('question_id', $question->id),
            );
            $answeredCount = $answers->filter(
                fn (ExamAnswer $answer): bool => $answer->answer !== null && $answer->answer !== '',
            )->count();
            $correctCount = $answers->where('is_correct', true)->count();

            return [
                'id' => $question->id,
                'number' => $question->number,
                'type' => $question->type->label(),
                'body' => strip_tags((string) $question->body),
                'score' => (float) $question->score,
                'answered_count' => $answeredCount,
                'correct_count' => $correctCount,
                'accuracy_percentage' => $answeredCount > 0
                    ? round($correctCount / $answeredCount * 100, 2)
                    : null,
            ];
        })->values();

        $participants = $attempts->map(fn (ExamAttempt $attempt): array => [
            'attempt_id' => $attempt->id,
            'participant_number' => $attempt->participant?->participant_number,
            'name' => $attempt->participant?->student?->name,
            'nisn' => $attempt->participant?->student?->nisn,
            'status' => $attempt->status->label(),
            'score' => $attempt->score,
            'max_score' => $attempt->max_score,
            'percentage' => $attempt->scorePercentage(),
            'time_spent_seconds' => $attempt->time_spent_seconds,
            'submitted_at' => $attempt->submitted_at?->toIso8601String(),
        ])->values();

        $scoredParticipants = $participants->whereNotNull('percentage');
        $scoreValues = $scoredParticipants->pluck('percentage');

        $distribution = [
            ['range' => '0–39', 'count' => $scoreValues->filter(fn (float $value): bool => $value < 40)->count()],
            ['range' => '40–59', 'count' => $scoreValues->filter(fn (float $value): bool => $value >= 40 && $value < 60)->count()],
            ['range' => '60–69', 'count' => $scoreValues->filter(fn (float $value): bool => $value >= 60 && $value < 70)->count()],
            ['range' => '70–79', 'count' => $scoreValues->filter(fn (float $value): bool => $value >= 70 && $value < 80)->count()],
            ['range' => '80–89', 'count' => $scoreValues->filter(fn (float $value): bool => $value >= 80 && $value < 90)->count()],
            ['range' => '90–100', 'count' => $scoreValues->filter(fn (float $value): bool => $value >= 90)->count()],
        ];

        $completedCount = $attempts->filter(
            fn (ExamAttempt $attempt): bool => $attempt->status !== ExamAttemptStatus::InProgress,
        )->count();

        return Inertia::render('Reports/Show', [
            'title' => 'Detail Laporan Ujian',
            'schedule' => [
                'id' => $examSchedule->id,
                'name' => $examSchedule->examPackage?->name,
                'code' => $examSchedule->examPackage?->code,
                'subject' => $examSchedule->subject?->name,
                'class' => $examSchedule->schoolClass?->name,
                'session' => $examSchedule->session?->name,
                'room' => $examSchedule->room?->name,
                'exam_date' => $examSchedule->exam_date?->format('Y-m-d'),
                'open_time' => $examSchedule->open_time,
                'close_time' => $examSchedule->close_time,
            ],
            'summary' => [
                'participant_count' => $attempts->count(),
                'completed_count' => $completedCount,
                'average_percentage' => $scoreValues->count() > 0
                    ? round($scoreValues->average(), 2)
                    : null,
                'pass_percentage' => $scoreValues->count() > 0
                    ? round($scoreValues->filter(fn (float $value): bool => $value >= 70)->count() / $scoreValues->count() * 100, 2)
                    : null,
                'highest_percentage' => $scoreValues->count() > 0 ? $scoreValues->max() : null,
                'lowest_percentage' => $scoreValues->count() > 0 ? $scoreValues->min() : null,
            ],
            'distribution' => $distribution,
            'questionAnalysis' => $questionAnalysis,
            'participants' => $participants,
        ]);
    }

    public function export(Request $request, ExamSchedule $examSchedule): BinaryFileResponse
    {
        $this->authorizeSchedule($request, $examSchedule);
        $examSchedule->loadMissing(['subject:id,name', 'schoolClass:id,name']);

        $attempts = ExamAttempt::query()
            ->where('exam_schedule_id', $examSchedule->id)
            ->with(['participant.student', 'answers:id,exam_attempt_id,is_correct'])
            ->orderBy('exam_participant_id')
            ->get();

        $filename = implode('-', array_filter([
            'laporan-ujian',
            Str::slug((string) $examSchedule->subject?->name),
            Str::slug((string) $examSchedule->schoolClass?->name),
            $examSchedule->id,
        ])).'.xlsx';

        return Excel::download(new ExamReportExport($attempts), $filename);
    }

    private function authorizeSchedule(Request $request, ExamSchedule $examSchedule): void
    {
        $examSchedule->loadMissing('examPackage');

        abort_unless(
            in_array((int) $examSchedule->examPackage?->school_id, $request->user()->accessibleSchoolIds(), true),
            404,
        );
    }
}
