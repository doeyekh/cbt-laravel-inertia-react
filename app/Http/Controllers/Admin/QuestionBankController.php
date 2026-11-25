<?php

namespace App\Http\Controllers\Admin;

use App\Enums\QuestionType;
use App\Http\Controllers\Admin\Concerns\PresentsQuestionBank;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreQuestionBankRequest;
use App\Http\Requests\Admin\UpdateQuestionBankRequest;
use App\Models\AcademicYear;
use App\Models\ExamType;
use App\Models\QuestionBank;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\SubjectTeacher;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class QuestionBankController extends Controller
{
    use PresentsQuestionBank;

    /**
     * Display a paginated listing of question banks.
     */
    public function index(Request $request): Response
    {
        $this->authorizeQuestionBankAccess($request->user());
        $user = $request->user();
        $accessibleSchoolIds = $user->accessibleSchoolIds();
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'school_id' => ['nullable', 'integer', 'min:1'],
            'subject_id' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $search = trim($filters['search'] ?? '');
        $schoolFilter = $filters['school_id'] ?? null;
        $subjectFilter = $filters['subject_id'] ?? null;
        $statusFilter = $filters['status'] ?? null;

        $activeAcademicYear = AcademicYear::active();
        $activeAcademicYearId = (int) ($activeAcademicYear?->id ?? 0);
        $isRestrictedGuru = $user->isRestrictedGuru();
        $isScopedGuru = $this->shouldScopeGuru($user, $activeAcademicYearId);
        $taughtSubjectIds = $isRestrictedGuru ? $user->taughtSubjectIds($activeAcademicYearId) : [];
        $taughtClassIds = $isRestrictedGuru ? $user->taughtClassIds(null, $activeAcademicYearId) : [];

        $subjectOptions = $this->subjectOptionsBySchool($accessibleSchoolIds, $activeAcademicYearId, $user, $isScopedGuru);

        /**
         * @var array<int, array{school_id: int, subject_ids: array<int, int>, class_ids_by_subject: array<string, array<int, int>>>>
         */
        $allowedAssignments = [];

        if ($isRestrictedGuru) {
            foreach ($subjectOptions as $option) {
                foreach ($option['subject_ids'] as $subjectId) {
                    $classIds = $option['class_ids_by_subject'][(string) $subjectId] ?? [];

                    // Abaikan mata pelajaran yang tidak memiliki kelas ampuan.
                    if ($classIds === []) {
                        continue;
                    }

                    $allowedAssignments[] = [
                        'school_id' => $option['school_id'],
                        'subject_id' => $subjectId,
                        'class_ids' => $classIds,
                    ];
                }
            }
        }

        $baseQuery = QuestionBank::query()
            ->whereIn('school_id', $accessibleSchoolIds)
            ->when($isRestrictedGuru, function ($query) use ($allowedAssignments): void {
                if ($allowedAssignments === []) {
                    $query->whereRaw('1 = 0');

                    return;
                }

                $query->where(function ($query) use ($allowedAssignments): void {
                    foreach ($allowedAssignments as $assignment) {
                        $query->orWhere(function ($query) use ($assignment): void {
                            $query->where('school_id', $assignment['school_id'])
                                ->where('subject_id', $assignment['subject_id'])
                                ->whereHas('classes', function ($query) use ($assignment): void {
                                    $query->whereIn('classes.id', $assignment['class_ids']);
                                });
                        });
                    }
                });
            })
            ->when($activeAcademicYearId > 0, fn ($query) => $query->where('academic_year_id', $activeAcademicYearId))
            ->when($schoolFilter > 0, fn ($query) => $query->where('school_id', (int) $schoolFilter))
            ->when($subjectFilter > 0, fn ($query) => $query->where('subject_id', (int) $subjectFilter))
            ->when($statusFilter === 'active', fn ($query) => $query->where('is_active', true))
            ->when($statusFilter === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('subject', function ($query) use ($search): void {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
            }));

        $questionBanks = (clone $baseQuery)
            ->with([
                'school:id,code,name',
                'subject:id,code,name',
                'examType:id,code,name',
                'academicYear:id,code,name',
                'classes:id,code,name',
            ])
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(
                fn (QuestionBank $questionBank): array => $this->presentQuestionBank($questionBank)
            );
        $stats = (clone $baseQuery)
            ->selectRaw('
        COUNT(*) AS total,

        COALESCE(
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END),
            0
        ) AS active,

        COALESCE(
            SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END),
            0
        ) AS inactive,

        COALESCE(SUM(
            COALESCE(pilihan_ganda_count, 0) +
            COALESCE(pilihan_ganda_kompleks_count, 0) +
            COALESCE(menjodohkan_count, 0) +
            COALESCE(isian_count, 0) +
            COALESCE(esai_count, 0)
        ), 0) AS question_total
    ')
            ->first();

        $stats = [
            'total' => (int) $stats->total,
            'active' => (int) $stats->active,
            'inactive' => (int) $stats->inactive,
            'question_total' => (int) $stats->question_total,
        ];

        $schools = School::query()
            ->whereIn('id', $accessibleSchoolIds)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'is_active']);

        $subjects = Subject::query()
            ->when($isRestrictedGuru, fn ($query) => $query->whereIn('id', $taughtSubjectIds))
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'is_active']);

        $examTypes = ExamType::query()
            ->whereIn('school_id', $accessibleSchoolIds)
            ->orderBy('name')
            ->get(['id', 'school_id', 'name', 'code', 'is_active']);
        $classes = SchoolClass::query()
            ->whereIn('school_id', $accessibleSchoolIds)
            ->when($isRestrictedGuru, fn ($query) => $query->whereIn('id', $taughtClassIds))
            ->when($activeAcademicYearId > 0, fn ($query) => $query->where('academic_year_id', $activeAcademicYearId))
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'school_id']);

        return Inertia::render('QuestionBanks/Index', [
            'title' => 'Bank Soal',
            'questionBanks' => $questionBanks,
            'schools' => $schools,
            'subjects' => $subjects,
            'subjectOptions' => $subjectOptions,
            'examTypes' => $examTypes,
            'classes' => $classes,
            'activeAcademicYear' => $activeAcademicYear === null ? null : [
                'id' => $activeAcademicYear->id,
                'code' => $activeAcademicYear->code,
                'name' => $activeAcademicYear->name,
            ],
            'types' => $this->typeOptions(),
            'filters' => [
                'search' => $search,
                'school_id' => $schoolFilter > 0 ? $schoolFilter : null,
                'subject_id' => $subjectFilter > 0 ? $subjectFilter : null,
                'status' => in_array($statusFilter, ['active', 'inactive'], true) ? $statusFilter : null,
            ],
            'stats' => $stats,
        ]);
    }

    /**
     * Store a newly created question bank.
     */
    public function store(StoreQuestionBankRequest $request): RedirectResponse
    {
        $this->authorizeQuestionBankManage($request->user());

        $data = $request->validated();

        $this->authorizeSchoolAccess($request->user(), (int) $data['school_id']);
        $this->authorizeSubjectClassScope(
            $request->user(),
            (int) $data['subject_id'],
            array_map('intval', $data['class_ids']),
        );

        $questionBank = DB::transaction(function () use ($data): QuestionBank {
            $questionBank = QuestionBank::create([
                'school_id' => (int) $data['school_id'],
                'subject_id' => (int) $data['subject_id'],
                'academic_year_id' => $this->activeAcademicYearId(),
                'exam_type_id' => isset($data['exam_type_id']) ? (int) $data['exam_type_id'] : null,
                'code' => strtoupper(trim((string) $data['code'])),
                'name' => trim((string) $data['name']),
                'description' => isset($data['description']) && trim((string) $data['description']) !== ''
                    ? trim((string) $data['description'])
                    : null,
                'pilihan_ganda_count' => (int) $data['pilihan_ganda_count'],
                'pilihan_ganda_kompleks_count' => (int) $data['pilihan_ganda_kompleks_count'],
                'menjodohkan_count' => (int) $data['menjodohkan_count'],
                'isian_count' => (int) $data['isian_count'],
                'esai_count' => (int) $data['esai_count'],
                'pilihan_ganda_score' => (float) $data['pilihan_ganda_score'],
                'pilihan_ganda_kompleks_score' => (float) $data['pilihan_ganda_kompleks_score'],
                'menjodohkan_score' => (float) $data['menjodohkan_score'],
                'isian_score' => (float) $data['isian_score'],
                'esai_score' => (float) $data['esai_score'],
                'pilihan_ganda_option_count' => (int) $data['pilihan_ganda_option_count'],
                'pilihan_ganda_kompleks_option_count' => (int) $data['pilihan_ganda_kompleks_option_count'],
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);

            $questionBank->classes()->sync($data['class_ids']);

            return $questionBank;
        });

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Bank Soal Ditambahkan',
            'description' => "Bank soal \"{$questionBank->name}\" (kode {$questionBank->code}) telah ditambahkan.",
        ]);
    }

    /**
     * Update the specified question bank.
     */
    public function update(UpdateQuestionBankRequest $request, QuestionBank $questionBank): RedirectResponse
    {
        $this->authorizeQuestionBankManage($request->user());
        $this->authorizeRecordAccess($request->user(), $questionBank);

        $data = $request->validated();

        $this->authorizeSchoolAccess($request->user(), (int) $data['school_id']);

        $classIds = array_key_exists('class_ids', $data)
            ? array_map('intval', $data['class_ids'])
            : $questionBank->classes()->pluck('classes.id')->map(fn ($classId): int => (int) $classId)->all();

        $this->authorizeSubjectClassScope(
            $request->user(),
            (int) $data['subject_id'],
            $classIds,
            $questionBank,
        );

        DB::transaction(function () use ($questionBank, $data): void {
            $questionBank->update([
                'school_id' => (int) $data['school_id'],
                'subject_id' => (int) $data['subject_id'],
                'exam_type_id' => array_key_exists('exam_type_id', $data)
                    ? $data['exam_type_id']
                    : $questionBank->exam_type_id,
                'code' => isset($data['code']) ? strtoupper(trim((string) $data['code'])) : $questionBank->code,
                'name' => isset($data['name']) ? trim((string) $data['name']) : $questionBank->name,
                'description' => array_key_exists('description', $data) ? $data['description'] : $questionBank->description,
                'pilihan_ganda_count' => $data['pilihan_ganda_count'] ?? $questionBank->pilihan_ganda_count,
                'pilihan_ganda_kompleks_count' => $data['pilihan_ganda_kompleks_count'] ?? $questionBank->pilihan_ganda_kompleks_count,
                'menjodohkan_count' => $data['menjodohkan_count'] ?? $questionBank->menjodohkan_count,
                'isian_count' => $data['isian_count'] ?? $questionBank->isian_count,
                'esai_count' => $data['esai_count'] ?? $questionBank->esai_count,
                'pilihan_ganda_score' => $data['pilihan_ganda_score'] ?? $questionBank->pilihan_ganda_score,
                'pilihan_ganda_kompleks_score' => $data['pilihan_ganda_kompleks_score'] ?? $questionBank->pilihan_ganda_kompleks_score,
                'menjodohkan_score' => $data['menjodohkan_score'] ?? $questionBank->menjodohkan_score,
                'isian_score' => $data['isian_score'] ?? $questionBank->isian_score,
                'esai_score' => $data['esai_score'] ?? $questionBank->esai_score,
                'pilihan_ganda_option_count' => $data['pilihan_ganda_option_count'] ?? $questionBank->pilihan_ganda_option_count,
                'pilihan_ganda_kompleks_option_count' => $data['pilihan_ganda_kompleks_option_count'] ?? $questionBank->pilihan_ganda_kompleks_option_count,
                'is_active' => $data['is_active'] ?? $questionBank->is_active,
            ]);

            if (isset($data['class_ids'])) {
                $questionBank->classes()->sync($data['class_ids']);
            }
        });

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Bank Soal Diperbarui',
            'description' => "Bank soal \"{$questionBank->name}\" (kode {$questionBank->code}) telah diperbarui.",
        ]);
    }

    /**
     * Remove the specified question bank.
     */
    public function destroy(Request $request, QuestionBank $questionBank): RedirectResponse
    {
        $this->authorizeQuestionBankManage($request->user());
        $this->authorizeRecordAccess($request->user(), $questionBank);

        $name = $questionBank->name;

        $questionBank->delete();

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Bank Soal Dihapus',
            'description' => "Bank soal \"{$name}\" telah dihapus.",
        ]);
    }

    /**
     * Determine whether the user may access the question bank module.
     */
    private function authorizeQuestionBankAccess(?User $user): void
    {
        if ($user === null) {
            abort(403);
        }

        if (! $user->isAdministrator() && ! $user->isGuru() && ! $user->isHeadmaster() && ! $user->isSchoolAdmin()) {
            abort(403);
        }
    }

    /**
     * Determine whether the user may make changes to question banks.
     */
    private function authorizeQuestionBankManage(User $user): void
    {
        if ($user->isAdministrator() || $user->isSchoolAdmin() || $user->isRestrictedGuru()) {
            return;
        }

        if ($user->isGuru() && $user->hasActiveTeachingAssignments($this->activeAcademicYearId())) {
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
     * Determine whether the user may access the given question bank.
     */
    private function authorizeRecordAccess(User $user, QuestionBank $questionBank): void
    {
        $this->authorizeSchoolAccess($user, (int) $questionBank->school_id);

        $academicYearId = (int) $questionBank->academic_year_id;

        if (! $this->shouldScopeGuru($user, $academicYearId)) {
            return;
        }

        $allowedClassIds = $user->taughtClassIds((int) $questionBank->subject_id, $academicYearId);

        $bankClassIds = $questionBank->classes()
            ->pluck('classes.id')
            ->map(fn ($classId): int => (int) $classId)
            ->all();
        if (array_intersect($bankClassIds, $allowedClassIds) === []) {
            abort(403, 'Anda hanya dapat mengakses bank soal untuk mata pelajaran dan kelas yang Anda ampu.');
        }
    }

    /**
     * Determine whether the guru may manage the subject and the selected classes.
     *
     * @param  array<int, int>  $classIds
     */
    private function authorizeSubjectClassScope(
        User $user,
        int $subjectId,
        array $classIds,
        ?QuestionBank $questionBank = null,
    ): void {
        $academicYearId = (int) ($questionBank?->academic_year_id ?? $this->activeAcademicYearId());

        if (! $this->shouldScopeGuru($user, $academicYearId)) {
            return;
        }

        if (! in_array($subjectId, $user->taughtSubjectIds($academicYearId), true)) {
            abort(403, 'Anda hanya dapat mengelola bank soal mata pelajaran yang Anda ampu.');
        }

        $allowedClassIds = $user->taughtClassIds($subjectId, $academicYearId);

        if ($questionBank !== null && (int) $questionBank->subject_id === $subjectId) {
            $allowedClassIds = array_values(array_unique([
                ...$allowedClassIds,
                ...$questionBank->classes()
                    ->pluck('classes.id')
                    ->map(fn ($classId): int => (int) $classId)
                    ->all(),
            ]));
        }

        if (array_diff($classIds, $allowedClassIds) !== []) {
            abort(403, 'Anda hanya dapat memilih kelas yang Anda ampu untuk mata pelajaran ini.');
        }
    }

    /**
     * Get the subjects mapped to each school's classes in the given academic year.
     *
     * @param  array<int, int>  $accessibleSchoolIds
     * @return array<int, array{school_id: int, subject_ids: array<int, int>, class_ids_by_subject: array<string, array<int, int>>}>
     */
    private function subjectOptionsBySchool(
        array $accessibleSchoolIds,
        int $academicYearId,
        ?User $user = null,
        bool $filterByTeacher = false,
    ): array {
        if ($academicYearId <= 0 || $accessibleSchoolIds === []) {
            return [];
        }

        return SubjectTeacher::query()
            ->where('subject_teachers.academic_year_id', $academicYearId)
            ->join('classes', 'classes.id', '=', 'subject_teachers.school_class_id')
            ->whereIn('classes.school_id', $accessibleSchoolIds)
            ->when($filterByTeacher, fn ($query) => $query->where('subject_teachers.teacher_id', $user?->id))
            ->get(['classes.school_id', 'subject_teachers.subject_id', 'subject_teachers.school_class_id'])
            ->groupBy('school_id')
            ->map(function ($subjectTeachers, $schoolId) use ($filterByTeacher): array {
                $classIdsBySubject = [];

                foreach ($subjectTeachers as $subjectTeacher) {
                    $classIdsBySubject[(string) $subjectTeacher->subject_id][] = (int) $subjectTeacher->school_class_id;
                }

                $option = [
                    'school_id' => (int) $schoolId,
                    'subject_ids' => $subjectTeachers->pluck('subject_id')
                        ->map(fn ($subjectId): int => (int) $subjectId)
                        ->unique()
                        ->values()
                        ->all(),
                ];

                if ($filterByTeacher) {
                    $option['class_ids_by_subject'] = array_map(
                        fn (array $classIds): array => array_values(array_unique($classIds)),
                        $classIdsBySubject,
                    );
                }

                return $option;
            })
            ->values()
            ->all();
    }

    /**
     * Determine whether the guru's access must be limited to the subjects and classes they teach.
     */
    private function shouldScopeGuru(User $user, int $academicYearId): bool
    {
        if ($user->isRestrictedGuru()) {
            return true;
        }

        return $user->isGuru() && $user->hasActiveTeachingAssignments($academicYearId);
    }

    /**
     * Get the id of the academic year a new question bank must belong to.
     */
    private function activeAcademicYearId(): int
    {
        return (int) (AcademicYear::active()?->id ?? 0);
    }

    /**
     * Get the question type options used by the composition inputs.
     *
     * @return array<int, array{value: string, label: string, count_field: string}>
     */
    private function typeOptions(): array
    {
        return array_map(
            fn (QuestionType $type): array => [
                'value' => $type->value,
                'label' => $type->label(),
                'count_field' => $type->countColumn(),
            ],
            QuestionType::cases(),
        );
    }
}
