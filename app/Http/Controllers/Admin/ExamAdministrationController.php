<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ClassMember;
use App\Models\ExamParticipant;
use App\Models\ExamSchedule;
use App\Models\ExamType;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\SubjectTeacher;
use App\Models\User;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Inertia\Response;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExamAdministrationController extends Controller
{
    /**
     * Display the exam administration download page.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $lockedSchoolId = $this->lockedSchoolId($user);
        $schoolIds = $lockedSchoolId !== null
            ? [$lockedSchoolId]
            : $user->accessibleSchoolIds();

        $school = School::query()
            ->whereIn('id', $schoolIds)
            ->orderBy('name')
            ->first(['id', 'name', 'code']);

        $activeAcademicYear = AcademicYear::active();

        $examTypes = ExamType::query()
            ->whereIn('school_id', $schoolIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'school_id', 'name', 'is_active']);

        $subjects = Subject::query()
            ->where(function (Builder $query) use ($schoolIds): void {
                $query
                    ->whereHas(
                        'examSchedules.examPackage',
                        fn (Builder $query) => $query->whereIn('school_id', $schoolIds),
                    )
                    ->orWhereHas(
                        'subjectTeachers.schoolClass',
                        fn (Builder $query) => $query->whereIn('school_id', $schoolIds),
                    );
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $classes = SchoolClass::query()
            ->whereIn('school_id', $schoolIds)
            ->when(
                $activeAcademicYear !== null,
                fn (Builder $query) => $query->where('academic_year_id', $activeAcademicYear->id),
                fn (Builder $query) => $query->whereRaw('1 = 0'),
            )
            ->orderBy('name')
            ->get(['id', 'school_id', 'academic_year_id', 'name']);

        $activeExamType = $examTypes->first();

        return Inertia::render('ExamAdministration/Index', [
            'title' => 'Administrasi Ujian',
            'school' => $school,
            'activeAcademicYear' => $activeAcademicYear !== null
                ? [
                    'id' => $activeAcademicYear->id,
                    'name' => $activeAcademicYear->name,
                    'semester_label' => $activeAcademicYear->semester?->label(),
                ]
                : null,
            'examTypes' => $examTypes,
            'subjects' => $subjects,
            'classes' => $classes,
            'filters' => [
                'school_id' => $school?->id,
                'academic_year_id' => $activeAcademicYear?->id,
                'exam_type_id' => $activeExamType?->id,
                'subject_id' => null,
                'class_id' => null,
            ],
        ]);
    }

    /**
     * Print attendance lists for a subject and exam type.
     */
    public function attendance(Request $request): StreamedResponse
    {
        $data = $this->validatedPrintFilters($request, requireSubject: true);

        $reports = $this->schedules($data)
            ->get()
            ->map(fn (ExamSchedule $schedule): array => [
                'schedule' => $schedule,
                'participants' => $this->attendanceParticipants($schedule, $data),
            ])
            ->values()
            ->all();

        $html = view('exam-administration.attendance', [
            'reports' => $reports,
            'print_date' => now()->format('d/m/Y H:i'),
        ])->render();

        return $this->pdfResponse(
            $html,
            'Daftar-Hadir-'.($data['subject_name'] ?? 'Mapel').'-'.now()->format('Ymd').'.pdf',
        );
    }

    /**
     * Print practical grade forms for a subject and exam type.
     *
     * Practical grades are based on the subject teacher assignments, not on
     * exam schedules, so every class taught the subject gets its own form.
     */
    public function practicalGrades(Request $request): StreamedResponse
    {
        $data = $this->validatedPrintFilters($request, requireSubject: true);
        $criteria = $this->criteriaFromRequest($request);

        $school = School::query()->findOrFail($data['school_id']);
        $academicYear = AcademicYear::query()->findOrFail($data['academic_year_id']);
        $examType = ExamType::query()->findOrFail($data['exam_type_id']);
        $subject = Subject::query()->findOrFail($data['subject_id']);

        $reports = SubjectTeacher::query()
            ->with([
                'teacher:id,name',
                'schoolClass:id,name',
            ])
            ->where('academic_year_id', $data['academic_year_id'])
            ->where('subject_id', $data['subject_id'])
            ->where('is_active', true)
            ->when(
                $data['class_id'] !== null,
                fn (Builder $query) => $query->where('school_class_id', $data['class_id']),
            )
            ->whereHas(
                'schoolClass',
                fn (Builder $query) => $query->where('school_id', $data['school_id']),
            )
            ->orderBy('school_class_id')
            ->get()
            ->unique('school_class_id')
            ->values()
            ->map(function (SubjectTeacher $assignment) use ($data, $school, $academicYear, $examType, $subject): array {
                $students = ClassMember::query()
                    ->where('class_id', $assignment->school_class_id)
                    ->where('academic_year_id', $data['academic_year_id'])
                    ->with('student:id,name,nisn')
                    ->get()
                    ->sortBy(fn (ClassMember $member): string => (string) $member->student?->name)
                    ->values()
                    ->map(fn (ClassMember $member, int $index): array => [
                        'number' => $index + 1,
                        'nisn' => $member->student?->nisn ?? '-',
                        'name' => $member->student?->name ?? '-',
                    ])
                    ->all();

                return [
                    'school' => $school,
                    'academic_year' => $academicYear,
                    'exam_type' => $examType,
                    'subject' => $subject,
                    'class' => $assignment->schoolClass,
                    'students' => $students,
                    'teacher_name' => $assignment->teacher?->name,
                ];
            })
            ->all();

        $html = view('exam-administration.practical-grades', [
            'reports' => $reports,
            'criteria' => $criteria,
            'print_date' => now()->format('d/m/Y H:i'),
        ])->render();

        return $this->mpdfResponse(
            $html,
            'Nilai-Praktek-'.($data['subject_name'] ?? 'Mapel').'-'.now()->format('Ymd').'.pdf',
        );
    }

    /**
     * Validate the shared print filters and enforce school access.
     *
     * @return array<string, mixed>
     */
    private function validatedPrintFilters(Request $request, bool $requireSubject = false): array
    {
        $validated = $request->validate([
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'exam_type_id' => ['required', 'integer', 'exists:exam_types,id'],
            'subject_id' => [$requireSubject ? 'required' : 'nullable', 'integer', 'exists:subjects,id'],
            'class_id' => ['nullable', 'integer', 'exists:classes,id'],
            'criteria' => ['nullable', 'string', 'max:500'],
        ]);

        $schoolId = (int) $validated['school_id'];

        $this->authorizeSchoolAccess($request, $schoolId);

        $examType = ExamType::query()
            ->whereKey((int) $validated['exam_type_id'])
            ->where('school_id', $schoolId)
            ->firstOrFail();

        $subjectId = ! empty($validated['subject_id'])
            ? (int) $validated['subject_id']
            : null;

        $classId = ! empty($validated['class_id'])
            ? (int) $validated['class_id']
            : null;

        if ($classId !== null && ! SchoolClass::query()
            ->whereKey($classId)
            ->where('school_id', $schoolId)
            ->exists()) {
            abort(403, 'Kelas tidak berada pada sekolah yang dipilih.');
        }

        return [
            'school_id' => $schoolId,
            'academic_year_id' => (int) $validated['academic_year_id'],
            'exam_type_id' => $examType->id,
            'exam_type_name' => $examType->name,
            'subject_id' => $subjectId,
            'subject_name' => $subjectId !== null
                ? Subject::query()->whereKey($subjectId)->value('name')
                : null,
            'class_id' => $classId,
        ];
    }

    /**
     * Build the schedule query for the selected exam type and subject.
     *
     * @param  array<string, mixed>  $data
     * @return Builder<ExamSchedule>
     */
    private function schedules(array $data): Builder
    {
        return ExamSchedule::query()
            ->whereHas('examPackage', function (Builder $query) use ($data): void {
                $query
                    ->where('school_id', $data['school_id'])
                    ->where('academic_year_id', $data['academic_year_id'])
                    ->where('exam_type_id', $data['exam_type_id']);
            })
            ->when(
                $data['subject_id'] !== null,
                fn (Builder $query) => $query->where('subject_id', $data['subject_id']),
            )
            ->when(
                $data['class_id'] !== null,
                fn (Builder $query) => $query->where('class_id', $data['class_id']),
            )
            ->with([
                'examPackage.school:id,name,admin_id',
                'examPackage.school.admin:id,name',
                'examPackage.academicYear:id,name,code',
                'examPackage.examType:id,name',
                'subject:id,name',
                'schoolClass:id,name',
                'session:id,name',
                'room:id,name',
            ])
            ->orderBy('exam_date')
            ->orderBy('open_time');
    }

    /**
     * Get the participants that belong to a schedule's class, session, and room.
     *
     * @param  array<string, mixed>  $data
     * @return array<int, array<string, string>>
     */
    private function attendanceParticipants(ExamSchedule $schedule, array $data): array
    {
        return ExamParticipant::query()
            ->where('school_id', $data['school_id'])
            ->where('academic_year_id', $data['academic_year_id'])
            ->when(
                $schedule->room_id !== null,
                fn (Builder $query) => $query->where('room_id', $schedule->room_id),
            )
            ->when(
                $schedule->session_id !== null,
                fn (Builder $query) => $query->where('session_id', $schedule->session_id),
            )
            ->whereHas(
                'student.classMembers',
                fn (Builder $query) => $query
                    ->where('class_id', $schedule->class_id)
                    ->where('academic_year_id', $data['academic_year_id']),
            )
            ->with('student:id,name')
            ->orderBy('participant_number')
            ->get()
            ->map(fn (ExamParticipant $participant): array => [
                'username' => $participant->username ?? '-',
                'participant_number' => $participant->participant_number ?? '-',
                'name' => $participant->student?->name ?? '-',
            ])
            ->values()
            ->all();
    }

    /**
     * Parse the practical assessment criteria.
     *
     * @return array<int, string>
     */
    private function criteriaFromRequest(Request $request): array
    {
        $criteria = collect(explode(',', (string) $request->string('criteria')))
            ->map(fn (string $criterion): string => trim($criterion))
            ->filter()
            ->unique()
            ->take(8)
            ->map(fn (string $criterion): string => mb_substr($criterion, 0, 40))
            ->values()
            ->all();

        return $criteria !== []
            ? $criteria
            : ['Persiapan', 'Pelaksanaan', 'Hasil', 'Sikap'];
    }

    /**
     * Ensure the user can print data for the selected school.
     */
    private function authorizeSchoolAccess(Request $request, int $schoolId): void
    {
        $user = $request->user();
        $lockedSchoolId = $this->lockedSchoolId($user);

        if ($lockedSchoolId !== null && $lockedSchoolId !== $schoolId) {
            abort(403, 'Anda tidak memiliki akses ke sekolah tersebut.');
        }

        if (! in_array($schoolId, $user->accessibleSchoolIds(), true)) {
            abort(403, 'Anda tidak memiliki akses ke sekolah tersebut.');
        }
    }

    /**
     * Resolve the school that locks the current school administrator.
     */
    private function lockedSchoolId(?User $user): ?int
    {
        if ($user === null) {
            return null;
        }

        $schoolId = $user->adminOf()->value('id') ?? $user->school_id;

        return $schoolId === null ? null : (int) $schoolId;
    }

    /**
     * Render an HTML string as a PDF response.
     */
    private function pdfResponse(
        string $html,
        string $filename,
        bool $landscape = false,
    ): StreamedResponse {
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
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', $landscape ? 'landscape' : 'portrait');
        $dompdf->render();

        return response()->stream(
            fn () => print ($dompdf->output()),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
            ],
        );
    }

    /**
     * Render an HTML string as an mPDF response.
     */
    private function mpdfResponse(
        string $html,
        string $filename,
        bool $landscape = false,
    ): StreamedResponse {
        $tempDir = storage_path('app/mpdf/temp');

        File::ensureDirectoryExists($tempDir);

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => $landscape ? 'A4-L' : 'A4',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
            'margin_header' => 5,
            'margin_footer' => 5,
            'default_font' => 'dejavusans',
            'default_font_size' => 8,
            'tempDir' => $tempDir,
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'useSubstitutions' => true,
        ]);

        $mpdf->SetTitle('Format Nilai Praktek');
        $mpdf->SetAuthor((string) config('app.name'));
        $mpdf->SetCreator((string) config('app.name'));

        $mpdf->WriteHTML($html);

        return response()->stream(
            static function () use ($mpdf, $filename): void {
                echo $mpdf->Output($filename, Destination::STRING_RETURN);
            },
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
                'Cache-Control' => 'private, max-age=0, must-revalidate',
            ],
        );
    }
}
