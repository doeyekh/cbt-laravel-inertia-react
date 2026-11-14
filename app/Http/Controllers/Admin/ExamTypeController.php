<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ExamPackageStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreExamTypeRequest;
use App\Http\Requests\Admin\UpdateExamTypeRequest;
use App\Models\ExamType;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ExamTypeController extends Controller
{
    /**
     * Display a paginated listing of exam types.
     */
    public function index(Request $request): Response
    {
        $this->authorizeExamAccess($request->user());

        $accessibleSchoolIds = $request->user()->accessibleSchoolIds();
        $search = trim((string) $request->string('search'));

        $schoolFilter = (int) $request->integer('school_id');

        if (! in_array($schoolFilter, $accessibleSchoolIds, true)) {
            $schoolFilter = 0;
        }

        $baseQuery = ExamType::query()
            ->whereIn('school_id', $accessibleSchoolIds)
            ->when($schoolFilter > 0, fn ($query) => $query->where('school_id', $schoolFilter));

        $examTypesPaginator = (clone $baseQuery)
            ->with('school')
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
            })
            ->latest()
            ->paginate(5)
            ->withQueryString()
            ->through(fn (ExamType $examType): array => $this->presentExamType($examType));

        $totalExamTypes = (clone $baseQuery)->count();
        $activeCount = (clone $baseQuery)->where('is_active', true)->count();

        $activeExamType = (clone $baseQuery)
            ->with('school')
            ->where('is_active', true)
            ->first();

        $schools = School::query()
            ->whereIn('id', $accessibleSchoolIds)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'is_active']);

        return Inertia::render('ExamTypes/Index', [
            'title' => 'Manajemen Jenis Ujian',
            'examTypes' => $examTypesPaginator,
            'schools' => $schools,
            'filters' => [
                'search' => $search,
                'school_id' => $schoolFilter > 0 ? $schoolFilter : null,
            ],
            'stats' => [
                'total' => $totalExamTypes,
                'active' => $activeExamType ? $this->presentExamType($activeExamType) : null,
                'inactive' => $totalExamTypes - $activeCount,
            ],
        ]);
    }

    /**
     * Store a newly created exam type.
     */
    public function store(StoreExamTypeRequest $request): RedirectResponse
    {
        $this->authorizeExamManage($request->user());

        $data = $request->validated();

        $this->authorizeSchoolAccess($request->user(), (int) $data['school_id']);

        $examType = DB::transaction(
            fn (): ExamType => ExamType::create([
                'school_id' => (int) $data['school_id'],
                'code' => strtoupper(trim((string) $data['code'])),
                'name' => trim((string) $data['name']),
                'description' => $data['description'] ?? null,
                'is_active' => false,
            ])
        );

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Berhasil Ditambahkan',
            'description' => "Jenis ujian \"{$examType->name}\" (kode {$examType->code}) telah ditambahkan.",
        ]);
    }

    /**
     * Update the specified exam type.
     */
    public function update(UpdateExamTypeRequest $request, ExamType $examType): RedirectResponse
    {
        $this->authorizeExamManage($request->user());
        $this->authorizeRecordAccess($request->user(), $examType);

        $data = $request->validated();

        $examType->update([
            'code' => isset($data['code']) ? strtoupper(trim((string) $data['code'])) : $examType->code,
            'name' => isset($data['name']) ? trim((string) $data['name']) : $examType->name,
            'description' => array_key_exists('description', $data) ? $data['description'] : $examType->description,
        ]);

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Berhasil Diperbarui',
            'description' => "Jenis ujian \"{$examType->name}\" (kode {$examType->code}) telah diperbarui.",
        ]);
    }

    /**
     * Toggle the active status of the specified exam type.
     * Lebih dari satu jenis ujian boleh aktif sekaligus.
     */
    public function toggleStatus(Request $request, $examTypeId): RedirectResponse
    {
        $this->authorizeExamManage($request->user());

        $examType = ExamType::findOrFail($examTypeId);

        $this->authorizeRecordAccess($request->user(), $examType);

        $wasActive = $examType->is_active;

        $affectedPackages = $examType->examPackages()
            ->where('status', '!=', ExamPackageStatus::Draft->value)
            ->count();

        $examType->update([
            'is_active' => ! $wasActive,
        ]);

        $isActive = $examType->is_active;

        $description = "Jenis ujian \"{$examType->name}\" sekarang ".($isActive ? 'aktif' : 'tidak aktif').'.';

        if ($affectedPackages > 0) {
            $description .= " {$affectedPackages} paket ujian dikembalikan ke status draft.";
        }

        return back()->with('toast', [
            'type' => 'success',
            'title' => $isActive ? 'Jenis Ujian Diaktifkan' : 'Jenis Ujian Dinonaktifkan',
            'description' => $description,
        ]);
    }

    /**
     * Remove the specified exam type.
     */
    public function destroy(Request $request, ExamType $examType): RedirectResponse
    {
        $this->authorizeExamManage($request->user());
        $this->authorizeRecordAccess($request->user(), $examType);

        $packageCount = $examType->examPackages()->count();

        if ($packageCount > 0) {
            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Gagal Menghapus',
                'description' => "Jenis ujian \"{$examType->name}\" masih digunakan oleh {$packageCount} paket ujian. Nonaktifkan jenis ujian terlebih dahulu.",
            ]);
        }

        $name = $examType->name;

        $examType->delete();

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Berhasil Dihapus',
            'description' => "Jenis ujian \"{$name}\" telah dihapus.",
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
     * Determine whether the user may make changes to exam management data.
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
     * Determine whether the user may access the given exam type.
     */
    private function authorizeRecordAccess(User $user, ExamType $examType): void
    {
        $this->authorizeSchoolAccess($user, (int) $examType->school_id);
    }

    /**
     * Transform an exam type into its frontend representation.
     *
     * @return array{id: int, school_id: int|null, school_name: string|null, school_code: string|null, code: string, name: string, description: string|null, is_active: bool}
     */
    private function presentExamType(ExamType $examType): array
    {
        return [
            'id' => $examType->id,
            'school_id' => $examType->school_id,
            'school_name' => $examType->school?->name,
            'school_code' => $examType->school?->code,
            'code' => $examType->code,
            'name' => $examType->name,
            'description' => $examType->description,
            'is_active' => (bool) $examType->is_active,
        ];
    }
}
