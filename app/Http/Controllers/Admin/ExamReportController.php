<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ExamAttemptStatus;
use App\Enums\QuestionType;
use App\Exports\ExamReportExport;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ClassMember;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\ExamParticipant;
use App\Models\ExamSchedule;
use App\Models\Question;
use App\Models\QuestionBlank;
use App\Models\QuestionOption;
use App\Models\QuestionPair;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\SubjectTeacher;
use App\Services\GradeExamAttemptService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
            ->whereHas('examPackage', fn($query) => $query->whereIn('school_id', $schoolIds))
            ->when($request->integer('academic_year_id') > 0, fn($query) => $query
                ->whereHas('examPackage', fn($packageQuery) => $packageQuery
                    ->where('academic_year_id', $request->integer('academic_year_id'))))
            ->when($request->integer('package_id') > 0, fn($query) => $query
                ->where('exam_package_id', $request->integer('package_id')))
            ->when($request->integer('subject_id') > 0, fn($query) => $query
                ->where('subject_id', $request->integer('subject_id')))
            ->when($request->integer('class_id') > 0, fn($query) => $query
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
            fn(ExamAttempt $attempt): bool => $attempt->status !== ExamAttemptStatus::InProgress,
        );
        $scoredAttempts = $completedAttempts->filter(
            fn(ExamAttempt $attempt): bool => $attempt->score !== null,
        );
        $averages = $scoredAttempts
            ->map(fn(ExamAttempt $attempt): float => $attempt->scorePercentage() ?? 0)
            ->values();

        $reportRows = $schedules->map(function (ExamSchedule $schedule) use ($attempts): array {
            $scheduleAttempts = $attempts->where('exam_schedule_id', $schedule->id);
            $completed = $scheduleAttempts->filter(
                fn(ExamAttempt $attempt): bool => $attempt->status !== ExamAttemptStatus::InProgress,
            );
            $scored = $completed->filter(fn(ExamAttempt $attempt): bool => $attempt->score !== null);
            $percentages = $scored
                ->map(fn(ExamAttempt $attempt): float => $attempt->scorePercentage() ?? 0);
            $passed = $percentages->filter(fn(float $percentage): bool => $percentage >= 70)->count();

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
            ->whereHas('examPackage', fn($query) => $query->whereIn('school_id', $schoolIds))
            ->distinct()
            ->pluck('subject_id');
        $packages = ExamPackage::query()
            ->whereIn('school_id', $schoolIds)
            ->when($request->integer('academic_year_id') > 0, fn($query) => $query
                ->where('academic_year_id', $request->integer('academic_year_id')))
            ->orderByDesc('start_date')
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn(ExamPackage $package): array => [
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
                ->map(fn(AcademicYear $year): array => ['id' => $year->id, 'name' => $year->name]),
            'subjects' => Subject::query()
                ->whereIn('id', $subjectIds)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn(Subject $subject): array => ['id' => $subject->id, 'name' => $subject->name]),
            'packages' => $packages,
            'classes' => SchoolClass::query()
                ->whereIn('school_id', $schoolIds)
                ->when($request->integer('academic_year_id') > 0, fn($query) => $query
                    ->where('academic_year_id', $request->integer('academic_year_id')))
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn(SchoolClass $class): array => ['id' => $class->id, 'name' => $class->name]),
            'reports' => $reportRows,
            'summary' => [
                'schedule_count' => $schedules->count(),
                'participant_count' => $attempts->unique('exam_participant_id')->count(),
                'completed_count' => $completedAttempts->count(),
                'average_percentage' => $averages->count() > 0
                    ? round($averages->average(), 2)
                    : null,
                'pass_percentage' => $averages->count() > 0
                    ? round($averages->filter(fn(float $value): bool => $value >= 70)->count() / $averages->count() * 100, 2)
                    : null,
            ],
        ]);
    }

    public function show(Request $request, ExamSchedule $examSchedule): InertiaResponse
    {
        return Inertia::render('Reports/Show', [
            'title' => 'Detail Laporan Ujian',
            ...$this->buildReportData($request, $examSchedule),
        ]);
    }

    public function pdf(Request $request, ExamSchedule $examSchedule): StreamedResponse
    {
        $data = $this->buildReportData($request, $examSchedule);
        $data['participants'] = $this->buildPdfParticipants($examSchedule);
        $data['schedule']['teacher'] = SubjectTeacher::query()
            ->with('teacher:id,name')
            ->where('school_class_id', $examSchedule->class_id)
            ->where('academic_year_id', $examSchedule->examPackage->academic_year_id)
            ->where('subject_id', $examSchedule->subject_id)
            ->where('is_active', true)
            ->first()
            ?->teacher
            ?->name;

        $options = new Options;
        $options->set([
            'isRemoteEnabled' => false,
            'isHtml5ParserEnabled' => true,
            'chroot' => [
                public_path(),
                storage_path('app/public'),
            ],
            'defaultFont' => 'DejaVu Sans',
            'defaultPaperSize' => 'A4',
            'dpi' => 96,
            'isPhpEnabled' => false,
        ]);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(
            view('reports.pdf', [
                ...$data,
                'print_date' => now()->format('d/m/Y H:i'),
            ])->render(),
            'UTF-8'
        );
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $canvas = $dompdf->getCanvas();
        $color = [0.35, 0.35, 0.4];

        $canvas->page_text(
            30,
            $canvas->get_height() - 24,
            'Laporan Ujian',
            'dejavu',
            8,
            $color,
        );

        $canvas->page_text(
            $canvas->get_width() - 150,
            $canvas->get_height() - 24,
            'Halaman {PAGE_NUM} dari {PAGE_COUNT}',
            'dejavu',
            8,
            $color,
        );

        $filename = $this->reportFilename($examSchedule, 'pdf');

        return response()->stream(
            fn() => print($dompdf->output()),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
            ],
        );
    }

    private function buildPdfParticipants(ExamSchedule $examSchedule): Collection
    {
        $examPackage = $examSchedule->examPackage;
        $classMembers = ClassMember::query()
            ->where('class_id', $examSchedule->class_id)
            ->where('academic_year_id', $examPackage->academic_year_id)
            ->with('student:id,name,nisn')
            ->get();
        $examParticipants = ExamParticipant::query()
            ->where('school_id', $examPackage->school_id)
            ->where('academic_year_id', $examPackage->academic_year_id)
            ->whereIn('student_id', $classMembers->pluck('student_id'))
            ->get()
            ->keyBy('student_id');
        $attemptsByStudentId = ExamAttempt::query()
            ->where('exam_schedule_id', $examSchedule->id)
            ->whereIn('exam_participant_id', $examParticipants->pluck('id'))
            ->with([
                'participant:id,student_id',
                'answers:id,exam_attempt_id,is_correct',
            ])
            ->get()
            ->keyBy(fn (ExamAttempt $attempt): ?int => $attempt->participant?->student_id);

        return $classMembers
            ->map(function (ClassMember $classMember) use ($examParticipants, $attemptsByStudentId): array {
                $student = $classMember->student;
                $examParticipant = $examParticipants->get($classMember->student_id);
                $attempt = $attemptsByStudentId->get($classMember->student_id);

                return [
                    'participant_number' => $examParticipant?->participant_number,
                    'name' => $student?->name,
                    'nisn' => $student?->nisn,
                    'correct_count' => $attempt?->answers->whereStrict('is_correct', true)->count(),
                    'wrong_count' => $attempt?->answers->whereStrict('is_correct', false)->count(),
                    'percentage' => $attempt?->scorePercentage(),
                    'time_spent_seconds' => $attempt?->time_spent_seconds,
                ];
            })
            ->sortBy(fn (array $participant): string => mb_strtolower((string) $participant['name']))
            ->values();
    }

    public function attempt(Request $request, ExamSchedule $examSchedule, ExamAttempt $examAttempt): InertiaResponse
    {
        $this->authorizeSchedule($request, $examSchedule);

        abort_unless((int) $examAttempt->exam_schedule_id === (int) $examSchedule->id, 404);

        $examSchedule->loadMissing([
            'examPackage:id,name,code,school_id,academic_year_id',
            'subject:id,name',
            'schoolClass:id,name',
        ]);

        $examAttempt->load([
            'participant.student',
            'answers.question.options',
            'answers.question.pairs',
            'answers.question.blanks',
        ]);

        $essayAnswers = $examAttempt->answers->filter(
            fn(ExamAnswer $answer): bool => $answer->question?->type === QuestionType::Esai
                && GradeExamAttemptService::hasAnswerContent(ExamAnswer::restoreAnswer($answer->answer)),
        );
        $canGradeEssay = $essayAnswers->isNotEmpty()
            && $examAttempt->status !== ExamAttemptStatus::InProgress;

        return Inertia::render('Reports/AttemptAnswers', [
            'title' => 'Jawaban Peserta',
            'schedule' => [
                'id' => $examSchedule->id,
                'name' => $examSchedule->examPackage?->name,
                'subject' => $examSchedule->subject?->name,
                'class' => $examSchedule->schoolClass?->name,
            ],
            'attempt' => [
                'id' => $examAttempt->id,
                'student_name' => $examAttempt->participant?->student?->name,
                'participant_number' => $examAttempt->participant?->participant_number,
                'nisn' => $examAttempt->participant?->student?->nisn,
                'status' => $examAttempt->status->label(),
                'score' => $examAttempt->score,
                'max_score' => $examAttempt->max_score,
                'percentage' => $examAttempt->scorePercentage(),
                'submitted_at' => $examAttempt->submitted_at?->toIso8601String(),
                'correct_count' => $examAttempt->answers->whereStrict('is_correct', true)->count(),
                'wrong_count' => $examAttempt->answers->whereStrict('is_correct', false)->count(),
                'pending_essay_count' => $essayAnswers
                    ->filter(fn(ExamAnswer $answer): bool => $answer->score_awarded === null)
                    ->count(),
                'essay_url' => $canGradeEssay
                    ? route('admin.exam-attempts.essays.index', $examAttempt)
                    : null,
            ],
            'questions' => $examAttempt->answers
                ->sortBy('question_no')
                ->map(fn(ExamAnswer $answer): array => $this->presentAttemptAnswer($answer))
                ->values(),
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

        return Excel::download(
            new ExamReportExport($attempts),
            $this->reportFilename($examSchedule, 'xlsx'),
        );
    }

    /**
     * Build the aggregated report data shared by the detail page and the PDF.
     *
     * @return array<string, mixed>
     */
    private function buildReportData(Request $request, ExamSchedule $examSchedule): array
    {
        $this->authorizeSchedule($request, $examSchedule);
        $examSchedule->load([
            'examPackage:id,name,code,school_id,academic_year_id',
            'examPackage.school:id,name',
            'examPackage.academicYear:id,name',
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
            ? $examSchedule->questionBank()->with(['questions' => fn($query) => $query
                ->where('is_active', true)
                ->orderBy('number')
                ->with(['options'])])->first()
            : null;

        $questions = $bank?->questions ?? collect();
        $questionAnalysis = $questions->map(function (Question $question) use ($attempts): array {
            $answers = $attempts->flatMap(
                fn(ExamAttempt $attempt): iterable => $attempt->answers
                    ->where('question_id', $question->id),
            );
            $answeredCount = $answers->filter(
                fn(ExamAnswer $answer): bool => $answer->answer !== null && $answer->answer !== '',
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

        $participants = $attempts->map(function (ExamAttempt $attempt) use ($examSchedule): array {
            $essayAnswers = $attempt->answers->filter(
                fn(ExamAnswer $answer): bool => $answer->question?->type === QuestionType::Esai
                    && GradeExamAttemptService::hasAnswerContent(ExamAnswer::restoreAnswer($answer->answer)),
            );
            $pendingEssayCount = $essayAnswers
                ->filter(fn(ExamAnswer $answer): bool => $answer->score_awarded === null)
                ->count();
            $canGradeEssay = $essayAnswers->isNotEmpty()
                && $attempt->status !== ExamAttemptStatus::InProgress;

            return [
                'attempt_id' => $attempt->id,
                'participant_number' => $attempt->participant?->participant_number,
                'name' => $attempt->participant?->student?->name,
                'nisn' => $attempt->participant?->student?->nisn,
                'status' => $attempt->status->label(),
                'score' => $attempt->score,
                'max_score' => $attempt->max_score,
                'percentage' => $attempt->scorePercentage(),
                'correct_count' => $attempt->answers->whereStrict('is_correct', true)->count(),
                'wrong_count' => $attempt->answers->whereStrict('is_correct', false)->count(),
                'pending_essay_count' => $pendingEssayCount,
                'grade_url' => $canGradeEssay
                    ? route('admin.exam-attempts.essays.index', $attempt)
                    : null,
                'answers_url' => route('admin.reports.attempt', [$examSchedule, $attempt]),
                'time_spent_seconds' => $attempt->time_spent_seconds,
                'submitted_at' => $attempt->submitted_at?->toIso8601String(),
            ];
        })->values();

        $scoredParticipants = $participants->whereNotNull('percentage');
        $scoreValues = $scoredParticipants->pluck('percentage');

        $distribution = [
            ['range' => '0–39', 'count' => $scoreValues->filter(fn(float $value): bool => $value < 40)->count()],
            ['range' => '40–59', 'count' => $scoreValues->filter(fn(float $value): bool => $value >= 40 && $value < 60)->count()],
            ['range' => '60–69', 'count' => $scoreValues->filter(fn(float $value): bool => $value >= 60 && $value < 70)->count()],
            ['range' => '70–79', 'count' => $scoreValues->filter(fn(float $value): bool => $value >= 70 && $value < 80)->count()],
            ['range' => '80–89', 'count' => $scoreValues->filter(fn(float $value): bool => $value >= 80 && $value < 90)->count()],
            ['range' => '90–100', 'count' => $scoreValues->filter(fn(float $value): bool => $value >= 90)->count()],
        ];

        $completedCount = $attempts->filter(
            fn(ExamAttempt $attempt): bool => $attempt->status !== ExamAttemptStatus::InProgress,
        )->count();

        return [
            'schedule' => [
                'id' => $examSchedule->id,
                'name' => $examSchedule->examPackage?->name,
                'code' => $examSchedule->examPackage?->code,
                'school' => $examSchedule->examPackage?->school?->name,
                'academic_year' => $examSchedule->examPackage?->academicYear?->name,
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
                    ? round($scoreValues->filter(fn(float $value): bool => $value >= 70)->count() / $scoreValues->count() * 100, 2)
                    : null,
                'highest_percentage' => $scoreValues->count() > 0 ? $scoreValues->max() : null,
                'lowest_percentage' => $scoreValues->count() > 0 ? $scoreValues->min() : null,
            ],
            'distribution' => $distribution,
            'questionAnalysis' => $questionAnalysis,
            'participants' => $participants,
        ];
    }

    /**
     * Build the answer review payload for a single exam answer.
     *
     * @return array<string, mixed>
     */
    private function presentAttemptAnswer(ExamAnswer $answer): array
    {
        $question = $answer->question;
        $restoredAnswer = ExamAnswer::restoreAnswer($answer->answer);

        $payload = [
            'id' => $question?->id,
            'number' => $answer->question_no,
            'type' => $question?->type?->value,
            'type_label' => $question?->type?->label(),
            'body' => $question?->body,
            'score' => (float) ($question?->score ?? 0),
            'is_answered' => GradeExamAttemptService::hasAnswerContent($restoredAnswer),
            'is_correct' => $answer->is_correct,
            'score_awarded' => $answer->score_awarded === null
                ? null
                : (float) $answer->score_awarded,
            'text_answer' => is_array($restoredAnswer)
                ? implode(', ', array_map(fn(mixed $value): string => (string) $value, $restoredAnswer))
                : (string) ($restoredAnswer ?? ''),
            'options' => [],
            'pairs' => [],
            'blanks' => [],
        ];

        if ($question === null) {
            return $payload;
        }

        if ($question->type === QuestionType::PilihanGanda || $question->type === QuestionType::PilihanGandaKompleks) {
            $selectedValues = collect(is_array($restoredAnswer) ? $restoredAnswer : [$restoredAnswer])
                ->filter(fn(mixed $value): bool => $value !== null && $value !== '')
                ->map(fn(mixed $value): string => (string) $value)
                ->values();

            $payload['options'] = $question->options
                ->sortBy('order')
                ->map(function (QuestionOption $option) use ($selectedValues): array {
                    $isSelected = $selectedValues->contains(
                        fn(string $value): bool => $value === (string) $option->id
                            || $value === $option->label
                            || $value === $option->text,
                    );

                    return [
                        'label' => $option->label,
                        'text' => $option->text,
                        'is_correct' => (bool) $option->is_correct,
                        'is_selected' => $isSelected,
                    ];
                })
                ->values()
                ->all();
        }

        if ($question->type === QuestionType::Menjodohkan) {
            $pairs = $question->pairs->sortBy('order')->values();
            $selectedIds = is_array($restoredAnswer) ? array_values($restoredAnswer) : [];

            $payload['pairs'] = $pairs
                ->map(function (QuestionPair $pair, int $index) use ($pairs, $selectedIds): array {
                    $selectedId = $selectedIds[$index] ?? null;
                    $selectedPair = $selectedId !== null
                        ? $pairs->first(
                            fn(QuestionPair $candidate): bool => (string) $candidate->id === (string) $selectedId,
                        )
                        : null;

                    return [
                        'left' => $pair->left_text,
                        'correct_right' => $pair->right_text,
                        'selected_right' => $selectedPair?->right_text,
                        'is_correct' => $selectedId !== null
                            && (string) $selectedId === (string) $pair->id,
                    ];
                })
                ->values()
                ->all();
        }

        if ($question->type === QuestionType::IsianSingkat) {
            $blankAnswers = is_array($restoredAnswer) ? array_values($restoredAnswer) : [$restoredAnswer];

            $payload['blanks'] = $question->blanks
                ->sortBy('order')
                ->values()
                ->map(function (QuestionBlank $blank, int $index) use ($blankAnswers): array {
                    $studentAnswer = (string) ($blankAnswers[$index] ?? '');
                    $acceptedAnswers = collect($blank->answers ?? [])
                        ->map(fn(mixed $accepted): string => (string) $accepted)
                        ->values();
                    $isCorrect = $studentAnswer !== ''
                        && $acceptedAnswers->contains(
                            fn(string $accepted): bool => GradeExamAttemptService::normalizeText($accepted)
                                === GradeExamAttemptService::normalizeText($studentAnswer),
                        );

                    return [
                        'order' => $index + 1,
                        'student_answer' => $studentAnswer,
                        'accepted_answers' => $acceptedAnswers->all(),
                        'is_correct' => $isCorrect,
                    ];
                })
                ->values()
                ->all();
        }

        return $payload;
    }

    private function reportFilename(ExamSchedule $examSchedule, string $extension): string
    {
        return implode('-', array_filter([
            'laporan-ujian',
            Str::slug((string) $examSchedule->subject?->name),
            Str::slug((string) $examSchedule->schoolClass?->name),
            $examSchedule->id,
        ])) . '.' . $extension;
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
