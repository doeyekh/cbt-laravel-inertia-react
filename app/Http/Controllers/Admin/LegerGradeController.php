<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ExamAttemptStatus;
use App\Exports\LegerGradeExport;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ExamAttempt;
use App\Models\ExamType;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LegerGradeController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Legers/Index', $this->buildData($request));
    }

    public function pdf(Request $request): StreamedResponse
    {
        $data = $this->buildData($request);

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
            view('leger-grades.pdf', [
                'title' => 'Leger Nilai',
                'rows' => $data['rows'],
                'columns' => $data['columns'],
                'summary' => $data['summary'],
                'filters' => $data['filters'],
                'print_date' => now()->format('d/m/Y H:i'),
            ])->render(),
            'UTF-8',
        );
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = 'Leger-Nilai-'.now()->format('Ymd').'.pdf';

        return response()->stream(
            fn () => print ($dompdf->output()),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
            ],
        );
    }

    public function export(Request $request): BinaryFileResponse
    {
        $data = $this->buildData($request);

        return Excel::download(
            new LegerGradeExport(collect($data['rows']), collect($data['columns'])),
            'Leger-Nilai-'.now()->format('Ymd').'.xlsx',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildData(Request $request): array
    {
        $filters = $this->validatedFilters($request);

        $schoolIds = $request->user()->accessibleSchoolIds();

        if ($schoolIds === [] && $request->user()?->isAdministrator() !== true) {
            abort(403, 'Tidak ada sekolah yang dapat diakses.');
        }

        if ($filters['school_id'] !== null && ! in_array($filters['school_id'], $schoolIds, true)) {
            abort(403, 'Anda tidak memiliki akses ke sekolah tersebut.');
        }

        $schools = School::query()
            ->whereIn('id', $schoolIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        $academicYears = AcademicYear::query()
            ->orderByDesc('name')
            ->get(['id', 'name']);

        $classes = SchoolClass::query()
            ->whereHas(
                'examSchedules.examPackage',
                fn ($query) => $query->whereIn('school_id', $schoolIds),
            )
            ->orderBy('name')
            ->get(['id', 'name']);

        $subjects = Subject::query()
            ->whereHas(
                'examSchedules.examPackage',
                fn ($query) => $query->whereIn('school_id', $schoolIds),
            )
            ->orderBy('name')
            ->get(['id', 'name']);

        $examTypes = ExamType::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $schoolId = $filters['school_id'];
        $academicYearId = $filters['academic_year_id'];
        $classId = $filters['class_id'];
        $subjectId = $filters['subject_id'];
        $examTypeId = $filters['exam_type_id'];
        $search = $filters['search'];

        $attempts = ExamAttempt::query()
            ->select([
                'exam_attempts.id',
                'exam_attempts.exam_participant_id',
                'exam_attempts.exam_schedule_id',
                'exam_attempts.status',
                'exam_attempts.score',
                'exam_attempts.max_score',
            ])
            ->with([
                'participant:id,student_id,school_id,academic_year_id,participant_number',
                'participant.student:id,name,nisn',
                'examSchedule:id,class_id,subject_id',
                'examSchedule.schoolClass:id,name',
                'examSchedule.subject:id,name',
                'examPackage:id,school_id,academic_year_id,exam_type_id,name,code',
                'examPackage.school:id,name',
                'examPackage.examType:id,name',
            ])
            ->whereNotNull('exam_attempts.score')
            ->whereIn('exam_attempts.status', [
                ExamAttemptStatus::Submitted->value,
                ExamAttemptStatus::Graded->value,
            ])
            ->whereHas('examPackage', function ($query) use (
                $schoolIds,
                $schoolId,
                $academicYearId,
                $examTypeId,
            ): void {
                $query->whereIn('school_id', $schoolIds);

                if ($schoolId !== null) {
                    $query->where('school_id', $schoolId);
                }

                if ($academicYearId !== null) {
                    $query->where('academic_year_id', $academicYearId);
                }

                if ($examTypeId !== null) {
                    $query->where('exam_type_id', $examTypeId);
                }
            })
            ->whereHas('examSchedule', function ($query) use ($classId, $subjectId): void {
                if ($classId !== null) {
                    $query->where('class_id', $classId);
                }

                if ($subjectId !== null) {
                    $query->where('subject_id', $subjectId);
                }
            })
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->whereHas('participant.student', function ($query) use ($search): void {
                            $query
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('nisn', 'like', "%{$search}%");
                        })
                        ->orWhereHas('participant', function ($query) use ($search): void {
                            $query->where('participant_number', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('exam_participant_id')
            ->orderBy('exam_schedule_id')
            ->get();

        $columns = collect();
        $grouped = collect();

        foreach ($attempts as $attempt) {
            $participant = $attempt->participant;
            $student = $participant?->student;
            $schedule = $attempt->examSchedule;
            $subject = $schedule?->subject;

            if ($participant === null || $student === null || $subject === null) {
                continue;
            }

            $key = $participant->id;

            if (! $grouped->has($key)) {
                $grouped->put($key, [
                    'participant_id' => $participant->id,
                    'student_id' => $student->id,
                    'participant_number' => $participant->participant_number,
                    'nisn' => $student->nisn,
                    'name' => $student->name,
                    'school' => $attempt->examPackage?->school?->name,
                    'class' => $schedule->schoolClass?->name,
                    'scores' => [],
                    'average' => null,
                    'completed_count' => 0,
                    'passed_count' => 0,
                ]);
            }

            $percentage = $attempt->scorePercentage();

            if ($percentage === null) {
                continue;
            }

            $columns->put($subject->id, $subject->name);

            $row = $grouped->get($key);

            $row['scores'][$subject->id] ??= ['total' => 0.0, 'count' => 0];
            $row['scores'][$subject->id]['total'] += $percentage;
            $row['scores'][$subject->id]['count']++;
            $row['completed_count']++;
            if ($percentage >= 70) {
                $row['passed_count']++;
            }

            $grouped->put($key, $row);
        }

        $columns = $columns->sortBy(fn (string $name): string => $name);

        $rows = $grouped
            ->map(function (array $row) use ($columns): array {
                $scores = [];
                $values = [];

                foreach ($columns as $subjectId => $subjectName) {
                    $entry = $row['scores'][$subjectId] ?? null;

                    $value = $entry !== null && $entry['count'] > 0
                        ? round($entry['total'] / $entry['count'], 2)
                        : null;

                    $scores[(string) $subjectId] = $value;

                    if ($value !== null) {
                        $values[] = $value;
                    }
                }

                $row['scores'] = $scores;
                $row['average'] = $values !== []
                    ? round(collect($values)->avg(), 2)
                    : null;

                return $row;
            })
            ->values()
            ->sortBy('name')
            ->values()
            ->all();

        $columnProps = $columns
            ->map(fn (string $name, int $id): array => [
                'id' => $id,
                'name' => $name,
            ])
            ->values()
            ->all();

        $averageValues = collect($rows)
            ->pluck('average')
            ->filter(fn ($value): bool => $value !== null)
            ->values();

        $summary = [
            'student_count' => count($rows),
            'subject_count' => count($columnProps),
            'average_percentage' => $averageValues->isNotEmpty()
                ? round($averageValues->avg(), 2)
                : null,
            'passed_percentage' => $averageValues->isNotEmpty()
                ? round(
                    $averageValues->filter(fn (float $value): bool => $value >= 70)->count()
                    / $averageValues->count()
                    * 100,
                    2,
                )
                : null,
            'highest_percentage' => $averageValues->max(),
            'lowest_percentage' => $averageValues->min(),
        ];

        $filters['labels'] = [
            'school' => $filters['school_id'] !== null
                ? ($schools->firstWhere('id', $filters['school_id'])?->name ?? '-')
                : 'Semua sekolah',
            'academic_year' => $filters['academic_year_id'] !== null
                ? ($academicYears->firstWhere('id', $filters['academic_year_id'])?->name ?? '-')
                : 'Semua tahun ajaran',
            'class' => $filters['class_id'] !== null
                ? ($classes->firstWhere('id', $filters['class_id'])?->name ?? '-')
                : 'Semua kelas',
            'subject' => $filters['subject_id'] !== null
                ? ($subjects->firstWhere('id', $filters['subject_id'])?->name ?? '-')
                : 'Semua mata pelajaran',
            'exam_type' => $filters['exam_type_id'] !== null
                ? ($examTypes->firstWhere('id', $filters['exam_type_id'])?->name ?? '-')
                : 'Semua jenis ujian',
        ];

        return [
            'title' => 'Leger Nilai',
            'rows' => $rows,
            'columns' => $columnProps,
            'summary' => $summary,
            'filters' => $filters,
            'schools' => $schools->values()->all(),
            'academicYears' => $academicYears->values()->all(),
            'classes' => $classes->values()->all(),
            'subjects' => $subjects->values()->all(),
            'examTypes' => $examTypes->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedFilters(Request $request): array
    {
        $validated = $request->validate([
            'school_id' => ['nullable', 'integer'],
            'academic_year_id' => ['nullable', 'integer'],
            'class_id' => ['nullable', 'integer'],
            'subject_id' => ['nullable', 'integer'],
            'exam_type_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        return [
            'school_id' => ! empty($validated['school_id']) ? (int) $validated['school_id'] : null,
            'academic_year_id' => ! empty($validated['academic_year_id']) ? (int) $validated['academic_year_id'] : null,
            'class_id' => ! empty($validated['class_id']) ? (int) $validated['class_id'] : null,
            'subject_id' => ! empty($validated['subject_id']) ? (int) $validated['subject_id'] : null,
            'exam_type_id' => ! empty($validated['exam_type_id']) ? (int) $validated['exam_type_id'] : null,
            'search' => trim((string) ($validated['search'] ?? '')),
        ];
    }
}
