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
        $search = trim((string) $request->string('search'));
        $schoolFilter = (int) $request->integer('school_id');
        $subjectFilter = (int) $request->integer('subject_id');
        $statusFilter = (string) $request->string('status');

        if (! in_array($schoolFilter, $accessibleSchoolIds, true)) {
            $schoolFilter = 0;
        }

        $activeAcademicYear = AcademicYear::active();
        $activeAcademicYearId = (int) ($activeAcademicYear?->id ?? 0);

        $baseQuery = QuestionBank::query()
            ->whereIn('school_id', $accessibleSchoolIds)
            ->when($activeAcademicYearId > 0, fn ($query) => $query->where('academic_year_id', $activeAcademicYearId))
            ->when($schoolFilter > 0, fn ($query) => $query->where('school_id', $schoolFilter))
            ->when($subjectFilter > 0, fn ($query) => $query->where('subject_id', $subjectFilter))
            ->when($statusFilter === 'active', fn ($query) => $query->where('is_active', true))
            ->when($statusFilter === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('subject', function ($query) use ($search): void {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('code', 'like', "%{$search}%");
                        });
                });
            });

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
            ->through(fn (QuestionBank $questionBank): array => $this->presentQuestionBank($questionBank));

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('is_active', true)->count(),
            'inactive' => (clone $baseQuery)->where('is_active', false)->count(),
            'question_total' => (int) (clone $baseQuery)
                ->selectRaw('COALESCE(SUM(pilihan_ganda_count), 0) + COALESCE(SUM(pilihan_ganda_kompleks_count), 0) + COALESCE(SUM(menjodohkan_count), 0) + COALESCE(SUM(isian_count), 0) + COALESCE(SUM(esai_count), 0) as total')
                ->value('total'),
        ];

        $schools = School::query()
            ->whereIn('id', $accessibleSchoolIds)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'is_active']);

        $subjects = Subject::query()
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'is_active']);

        $examTypes = ExamType::query()
            ->whereIn('school_id', $accessibleSchoolIds)
            ->orderBy('name')
            ->get(['id', 'school_id', 'code', 'name', 'is_active']);

        $classes = SchoolClass::query()
            ->whereIn('school_id', $accessibleSchoolIds)
            ->when($activeAcademicYearId > 0, fn ($query) => $query->where('academic_year_id', $activeAcademicYearId))
            ->orderBy('name')
            ->get(['id', 'school_id', 'code', 'name']);

        return Inertia::render('QuestionBanks/Index', [
            'title' => 'Bank Soal',
            'questionBanks' => $questionBanks,
            'schools' => $schools,
            'subjects' => $subjects,
            'subjectOptions' => $this->subjectOptionsBySchool($accessibleSchoolIds, $activeAcademicYearId),
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

        if (! $user->isAdministrator() && ! $user->isHeadmaster() && ! $user->isSchoolAdmin()) {
            abort(403);
        }
    }

    /**
     * Determine whether the user may make changes to question banks.
     */
    private function authorizeQuestionBankManage(User $user): void
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
     * Determine whether the user may access the given question bank.
     */
    private function authorizeRecordAccess(User $user, QuestionBank $questionBank): void
    {
        $this->authorizeSchoolAccess($user, (int) $questionBank->school_id);
    }

    /**
     * Get the subjects mapped to each school's classes in the given academic year.
     *
     * @param  array<int, int>  $accessibleSchoolIds
     * @return array<int, array{school_id: int, subject_ids: array<int, int>}>
     */
    private function subjectOptionsBySchool(array $accessibleSchoolIds, int $academicYearId): array
    {
        if ($academicYearId <= 0 || $accessibleSchoolIds === []) {
            return [];
        }

        return SubjectTeacher::query()
            ->where('subject_teachers.academic_year_id', $academicYearId)
            ->join('classes', 'classes.id', '=', 'subject_teachers.school_class_id')
            ->whereIn('classes.school_id', $accessibleSchoolIds)
            ->get(['classes.school_id', 'subject_teachers.subject_id'])
            ->groupBy('school_id')
            ->map(fn ($subjectTeachers, $schoolId): array => [
                'school_id' => (int) $schoolId,
                'subject_ids' => $subjectTeachers->pluck('subject_id')->unique()->values()->all(),
            ])
            ->values()
            ->all();
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
