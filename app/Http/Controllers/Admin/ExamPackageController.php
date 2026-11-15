<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ExamPackageStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreExamPackageRequest;
use App\Http\Requests\Admin\UpdateExamPackageRequest;
use App\Models\AcademicYear;
use App\Models\ExamPackage;
use App\Models\ExamSchedule;
use App\Models\ExamType;
use App\Models\QuestionBank;
use App\Models\Room;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Session;
use App\Models\Subject;
use App\Models\User;
use App\Services\ExamAccessTokenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ExamPackageController extends Controller
{
    /**
     * Display a paginated listing of exam packages.
     */
    public function index(Request $request): Response
    {
        $this->authorizeExamAccess($request->user());

        $user = $request->user();
        $accessibleSchoolIds = $user->accessibleSchoolIds();
        $search = trim((string) $request->string('search'));
        $schoolFilter = (int) $request->integer('school_id');
        $examTypeFilter = (int) $request->integer('exam_type_id');
        $statusFilter = ExamPackageStatus::tryFrom((string) $request->string('status'));

        if (! in_array($schoolFilter, $accessibleSchoolIds, true)) {
            $schoolFilter = 0;
        }

        $activeAcademicYear = AcademicYear::active();
        $activeAcademicYearId = (int) ($activeAcademicYear?->id ?? 0);

        $baseQuery = ExamPackage::query()
            ->whereIn('school_id', $accessibleSchoolIds)
            ->when($activeAcademicYearId > 0, fn ($query) => $query->where('academic_year_id', $activeAcademicYearId))
            ->when($schoolFilter > 0, fn ($query) => $query->where('school_id', $schoolFilter))
            ->when($examTypeFilter > 0, fn ($query) => $query->where('exam_type_id', $examTypeFilter))
            ->when($statusFilter !== null, fn ($query) => $query->where('status', $statusFilter->value))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('school', function ($query) use ($search): void {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('code', 'like', "%{$search}%");
                        });
                });
            });

        $packages = (clone $baseQuery)
            ->with(['school:id,name,code', 'academicYear:id,code,name', 'examType:id,code,name,is_active'])
            ->withCount('schedules')
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (ExamPackage $examPackage): array => $this->presentExamPackage($examPackage));

        $total = (clone $baseQuery)->count();
        $draft = (clone $baseQuery)->where('status', ExamPackageStatus::Draft->value)->count();
        $active = (clone $baseQuery)->where('status', ExamPackageStatus::Aktif->value)->count();
        $completed = (clone $baseQuery)->where('status', ExamPackageStatus::Selesai->value)->count();

        $schools = School::query()
            ->whereIn('id', $accessibleSchoolIds)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'is_active']);

        $examTypes = ExamType::query()
            ->whereIn('school_id', $accessibleSchoolIds)
            ->orderBy('name')
            ->get(['id', 'school_id', 'code', 'name', 'is_active']);

        return Inertia::render('ExamPackages/Index', [
            'title' => 'Paket Ujian',
            'examPackages' => $packages,
            'schools' => $schools,
            'activeAcademicYear' => $activeAcademicYear === null ? null : [
                'id' => $activeAcademicYear->id,
                'code' => $activeAcademicYear->code,
                'name' => $activeAcademicYear->name,
            ],
            'examTypes' => $examTypes,
            'statuses' => $this->statusOptions(),
            'filters' => [
                'search' => $search,
                'school_id' => $schoolFilter > 0 ? $schoolFilter : null,
                'exam_type_id' => $examTypeFilter > 0 ? $examTypeFilter : null,
                'status' => $statusFilter?->value,
            ],
            'stats' => [
                'total' => $total,
                'draft' => $draft,
                'active' => $active,
                'completed' => $completed,
            ],
        ]);
    }

    /**
     * Display the specified exam package with its schedules.
     */
    public function show(Request $request, ExamPackage $examPackage): Response
    {
        $this->authorizeExamAccess($request->user());
        $this->authorizeRecordAccess($request->user(), $examPackage);

        $examPackage->load([
            'school:id,name,code',
            'academicYear:id,code,name',
            'examType:id,code,name,is_active',
            'schedules' => fn ($query) => $query
                ->with([
                    'schoolClass:id,code,name',
                    'subject:id,code,name',
                    'session:id,code,name,start_time,end_time',
                    'room:id,code,name',
                ])
                ->orderBy('exam_date')
                ->orderBy('session_id'),
        ]);

        $classes = SchoolClass::query()
            ->where('school_id', $examPackage->school_id)
            ->where('academic_year_id', $examPackage->academic_year_id)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        $subjectsByClass = $this->subjectsByClass($examPackage, $classes->pluck('id')->all());

        $sessions = Session::query()
            ->where('school_id', $examPackage->school_id)
            ->orderBy('start_time')
            ->get(['id', 'code', 'name', 'start_time', 'end_time']);

        $rooms = Room::query()
            ->where('school_id', $examPackage->school_id)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        $presentedExamPackage = $this->presentExamPackage($examPackage);

        if ($request->user()->isAdministrator() || $request->user()->isSchoolAdmin()) {
            $presentedExamPackage['access_token'] = $examPackage->exam_access_token_encrypted;
        }

        return Inertia::render('ExamPackages/Show', [
            'title' => 'Detail Paket Ujian',
            'examPackage' => $presentedExamPackage,
            'schedules' => $examPackage->schedules
                ->map(fn (ExamSchedule $examSchedule): array => $this->presentExamSchedule($examSchedule))
                ->values()
                ->all(),
            'classes' => $classes,
            'subjectsByClass' => $subjectsByClass,
            'sessions' => $sessions,
            'rooms' => $rooms,
        ]);
    }

    /**
     * Generate or rotate the shared access token for the exam package.
     */
    public function generateAccessToken(
        Request $request,
        ExamPackage $examPackage,
        ExamAccessTokenService $tokenService,
    ): RedirectResponse {
        $this->authorizeExamManage($request->user());
        $this->authorizeRecordAccess($request->user(), $examPackage);

        if ($examPackage->status === ExamPackageStatus::Selesai) {
            abort(403);
        }

        $token = $tokenService->generateToken();

        $examPackage->forceFill([
            'exam_access_token_hash' => $tokenService->hashToken($token),
            'exam_access_token_encrypted' => $token,
            'exam_access_token_generated_at' => now(),
        ])->save();

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Token Akses Dibuat',
            'description' => 'Token tersimpan dan dapat dilihat kembali oleh pengelola paket.',
        ]);
    }

    /**
     * Revoke the shared access token for the exam package.
     */
    public function revokeAccessToken(Request $request, ExamPackage $examPackage): RedirectResponse
    {
        $this->authorizeExamManage($request->user());
        $this->authorizeRecordAccess($request->user(), $examPackage);

        $examPackage->forceFill([
            'exam_access_token_hash' => null,
            'exam_access_token_encrypted' => null,
            'exam_access_token_generated_at' => null,
        ])->save();

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Token Akses Dicabut',
            'description' => 'Peserta tidak dapat memakai token sebelumnya.',
        ]);
    }

    /**
     * Store a newly created exam package.
     */
    public function store(StoreExamPackageRequest $request): RedirectResponse
    {
        $this->authorizeExamManage($request->user());

        $data = $request->validated();

        $this->authorizeSchoolAccess($request->user(), (int) $data['school_id']);

        $activeAcademicYear = AcademicYear::active();

        if ($activeAcademicYear === null) {
            return back()
                ->withErrors(['academic_year_id' => 'Belum ada tahun pelajaran aktif.'])
                ->withInput();
        }

        $examPackage = DB::transaction(
            fn (): ExamPackage => ExamPackage::create([
                'school_id' => (int) $data['school_id'],
                'academic_year_id' => $activeAcademicYear->id,
                'exam_type_id' => (int) $data['exam_type_id'],
                'code' => strtoupper(trim((string) $data['code'])),
                'name' => trim((string) $data['name']),
                'description' => isset($data['description']) ? trim((string) $data['description']) : null,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'status' => ExamPackageStatus::from((string) $data['status']),
            ])
        );

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Berhasil Ditambahkan',
            'description' => "Paket ujian \"{$examPackage->name}\" (kode {$examPackage->code}) telah ditambahkan.",
        ]);
    }

    /**
     * Update the specified exam package.
     */
    public function update(UpdateExamPackageRequest $request, ExamPackage $examPackage): RedirectResponse
    {
        $this->authorizeExamManage($request->user());
        $this->authorizeRecordAccess($request->user(), $examPackage);

        $data = $request->validated();

        $examPackage->update([
            'exam_type_id' => $data['exam_type_id'] ?? $examPackage->exam_type_id,
            'code' => isset($data['code']) ? strtoupper(trim((string) $data['code'])) : $examPackage->code,
            'name' => isset($data['name']) ? trim((string) $data['name']) : $examPackage->name,
            'description' => array_key_exists('description', $data) ? $data['description'] : $examPackage->description,
            'start_date' => $data['start_date'] ?? $examPackage->start_date,
            'end_date' => $data['end_date'] ?? $examPackage->end_date,
            'status' => isset($data['status']) ? ExamPackageStatus::from((string) $data['status']) : $examPackage->status,
        ]);

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Berhasil Diperbarui',
            'description' => "Paket ujian \"{$examPackage->name}\" (kode {$examPackage->code}) telah diperbarui.",
        ]);
    }

    /**
     * Remove the specified exam package.
     */
    public function destroy(Request $request, ExamPackage $examPackage): RedirectResponse
    {
        $this->authorizeExamManage($request->user());
        $this->authorizeRecordAccess($request->user(), $examPackage);

        $name = $examPackage->name;

        $examPackage->delete();

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Berhasil Dihapus',
            'description' => "Paket ujian \"{$name}\" beserta jadwalnya telah dihapus.",
        ]);
    }

    /**
     * Determine whether the user may access the exam management module.
     */
    private function authorizeExamAccess(?User $user): void
    {
        if ($user === null) {
            abort(403);
        }

        if (! $user->isAdministrator() && ! $user->isHeadmaster() && ! $user->isSchoolAdmin()) {
            abort(403);
        }
    }

    /**
     * Determine whether the user may make changes to exam packages.
     */
    private function authorizeExamManage(User $user): void
    {
        if ($user->isAdministrator() || $user->isSchoolAdmin()) {
            return;
        }

        abort(403);
    }

    /**
     * Determine whether the user may use the given school.
     */
    private function authorizeSchoolAccess(User $user, int $schoolId): void
    {
        if (! in_array($schoolId, $user->accessibleSchoolIds(), true)) {
            abort(403);
        }
    }

    /**
     * Determine whether the user may access the given exam package.
     */
    private function authorizeRecordAccess(User $user, ExamPackage $examPackage): void
    {
        $this->authorizeSchoolAccess($user, (int) $examPackage->school_id);
    }

    /**
     * Transform an exam package into its frontend representation.
     *
     * @return array<string, mixed>
     */
    private function presentExamPackage(ExamPackage $examPackage): array
    {
        return [
            'id' => $examPackage->id,
            'school_id' => $examPackage->school_id,
            'school_name' => $examPackage->school?->name,
            'school_code' => $examPackage->school?->code,
            'academic_year_id' => $examPackage->academic_year_id,
            'academic_year_name' => $examPackage->academicYear?->name,
            'academic_year_semester' => $examPackage->academicYear?->semester?->value,
            'exam_type_id' => $examPackage->exam_type_id,
            'exam_type_name' => $examPackage->examType?->name,
            'exam_type_code' => $examPackage->examType?->code,
            'exam_type_is_active' => (bool) ($examPackage->examType?->is_active),
            'code' => $examPackage->code,
            'name' => $examPackage->name,
            'description' => $examPackage->description,
            'start_date' => $examPackage->start_date?->toDateString(),
            'end_date' => $examPackage->end_date?->toDateString(),
            'status' => $examPackage->status->value,
            'status_label' => $examPackage->status->label(),
            'has_access_token' => $examPackage->exam_access_token_hash !== null,
            'schedule_count' => $examPackage->schedules_count ?? $examPackage->schedules->count(),
        ];
    }

    /**
     * Transform an exam schedule into its frontend representation.
     *
     * @return array<string, mixed>
     */
    private function presentExamSchedule(ExamSchedule $examSchedule): array
    {
        return [
            'id' => $examSchedule->id,
            'class_id' => $examSchedule->class_id,
            'class_name' => $examSchedule->schoolClass?->name,
            'class_code' => $examSchedule->schoolClass?->code,
            'subject_id' => $examSchedule->subject_id,
            'subject_name' => $examSchedule->subject?->name,
            'subject_code' => $examSchedule->subject?->code,
            'session_id' => $examSchedule->session_id,
            'session_name' => $examSchedule->session?->name,
            'session_start_time' => $examSchedule->session?->start_time,
            'session_end_time' => $examSchedule->session?->end_time,
            'room_id' => $examSchedule->room_id,
            'room_name' => $examSchedule->room?->name,
            'room_code' => $examSchedule->room?->code,
            'exam_date' => $examSchedule->exam_date?->toDateString(),
            'duration_minutes' => $examSchedule->duration_minutes,
            'open_time' => $examSchedule->open_time,
            'close_time' => $examSchedule->close_time,
            'shuffle_questions' => (bool) $examSchedule->shuffle_questions,
            'shuffle_options' => (bool) $examSchedule->shuffle_options,
            'show_score_to_student' => (bool) $examSchedule->show_score_to_student,
            'status' => $examSchedule->status->value,
            'status_label' => $examSchedule->status->label(),
        ];
    }

    /**
     * Get the subjects that have a question bank for the given exam package, grouped by class.
     *
     * @param  array<int, int>  $classIds
     * @return array<int, array<int, Subject>>
     */
    private function subjectsByClass(ExamPackage $examPackage, array $classIds): array
    {
        if ($classIds === []) {
            return [];
        }

        $subjectIdsByClass = [];

        QuestionBank::query()
            ->where('school_id', $examPackage->school_id)
            ->where('academic_year_id', $examPackage->academic_year_id)
            ->where('exam_type_id', $examPackage->exam_type_id)
            ->whereHas('classes', fn ($query) => $query->whereIn('classes.id', $classIds))
            ->with('classes:id')
            ->get(['id', 'subject_id'])
            ->each(function (QuestionBank $questionBank) use (&$subjectIdsByClass, $classIds): void {
                foreach ($questionBank->classes as $schoolClass) {
                    if (in_array((int) $schoolClass->id, $classIds, true)) {
                        $subjectIdsByClass[$schoolClass->id][] = (int) $questionBank->subject_id;
                    }
                }
            });

        if ($subjectIdsByClass === []) {
            return [];
        }

        $subjects = Subject::query()
            ->whereIn('id', array_unique(array_merge(...array_values($subjectIdsByClass))))
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        $subjectsByClass = [];

        foreach ($subjectIdsByClass as $classId => $subjectIds) {
            $wantedSubjectIds = array_flip($subjectIds);

            $subjectsByClass[$classId] = $subjects
                ->filter(fn (Subject $subject): bool => isset($wantedSubjectIds[$subject->id]))
                ->values()
                ->all();
        }

        ksort($subjectsByClass);

        return $subjectsByClass;
    }

    /**
     * Get the status options for filters and forms.
     *
     * @return array<int, array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        return array_map(
            fn (ExamPackageStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ],
            ExamPackageStatus::cases(),
        );
    }
}
