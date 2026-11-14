<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAssessmentTypeRequest;
use App\Http\Requests\Admin\UpdateAssessmentTypeRequest;
use App\Models\AssessmentType;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AssessmentTypeController extends Controller
{
    /**
     * Display a paginated listing of assessment types.
     */
    public function index(Request $request): Response
    {
        $this->authorizeAssessmentAccess($request->user());

        $accessibleSchoolIds = $request->user()->accessibleSchoolIds();
        $search = trim((string) $request->string('search'));

        $schoolFilter = (int) $request->integer('school_id');

        if (! in_array($schoolFilter, $accessibleSchoolIds, true)) {
            $schoolFilter = 0;
        }

        $baseQuery = AssessmentType::query()
            ->whereIn('school_id', $accessibleSchoolIds)
            ->when($schoolFilter > 0, fn ($query) => $query->where('school_id', $schoolFilter));

        $assessmentTypesPaginator = (clone $baseQuery)
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
            ->through(fn (AssessmentType $assessmentType): array => $this->presentAssessmentType($assessmentType));

        $totalAssessmentTypes = (clone $baseQuery)->count();
        $activeCount = (clone $baseQuery)->where('is_active', true)->count();
        $totalWeight = (float) (clone $baseQuery)->sum('weight');

        $activeAssessmentType = (clone $baseQuery)
            ->with('school')
            ->where('is_active', true)
            ->first();

        $schools = School::query()
            ->whereIn('id', $accessibleSchoolIds)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'is_active']);

        return Inertia::render('AssessmentTypes/Index', [
            'title' => 'Manajemen Jenis Penilaian',
            'assessmentTypes' => $assessmentTypesPaginator,
            'schools' => $schools,
            'filters' => [
                'search' => $search,
                'school_id' => $schoolFilter > 0 ? $schoolFilter : null,
            ],
            'stats' => [
                'total' => $totalAssessmentTypes,
                'active' => $activeAssessmentType ? $this->presentAssessmentType($activeAssessmentType) : null,
                'inactive' => $totalAssessmentTypes - $activeCount,
                'total_weight' => round($totalWeight, 2),
            ],
        ]);
    }

    /**
     * Store a newly created assessment type.
     */
    public function store(StoreAssessmentTypeRequest $request): RedirectResponse
    {
        $this->authorizeAssessmentManage($request->user());

        $data = $request->validated();

        $this->authorizeSchoolAccess($request->user(), (int) $data['school_id']);

        $assessmentType = DB::transaction(
            fn (): AssessmentType => AssessmentType::create([
                'school_id' => (int) $data['school_id'],
                'code' => strtoupper(trim((string) $data['code'])),
                'name' => trim((string) $data['name']),
                'description' => $data['description'] ?? null,
                'weight' => (float) $data['weight'],
                'is_active' => false,
            ])
        );

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Berhasil Ditambahkan',
            'description' => "Jenis penilaian \"{$assessmentType->name}\" (kode {$assessmentType->code}) dengan bobot {$assessmentType->weight}% telah ditambahkan.",
        ]);
    }

    /**
     * Update the specified assessment type.
     */
    public function update(UpdateAssessmentTypeRequest $request, AssessmentType $assessmentType): RedirectResponse
    {
        $this->authorizeAssessmentManage($request->user());
        $this->authorizeRecordAccess($request->user(), $assessmentType);

        $data = $request->validated();

        $assessmentType->update([
            'code' => isset($data['code']) ? strtoupper(trim((string) $data['code'])) : $assessmentType->code,
            'name' => isset($data['name']) ? trim((string) $data['name']) : $assessmentType->name,
            'description' => array_key_exists('description', $data) ? $data['description'] : $assessmentType->description,
            'weight' => array_key_exists('weight', $data) ? (float) $data['weight'] : $assessmentType->weight,
        ]);

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Berhasil Diperbarui',
            'description' => "Jenis penilaian \"{$assessmentType->name}\" (kode {$assessmentType->code}) telah diperbarui.",
        ]);
    }

    /**
     * Toggle the active status of the specified assessment type.
     * Lebih dari satu jenis penilaian boleh aktif sekaligus.
     */
    public function toggleStatus(Request $request, $assessmentTypeId): RedirectResponse
    {
        $this->authorizeAssessmentManage($request->user());

        $assessmentType = AssessmentType::findOrFail($assessmentTypeId);

        $this->authorizeRecordAccess($request->user(), $assessmentType);

        $wasActive = $assessmentType->is_active;

        $assessmentType->update([
            'is_active' => ! $wasActive,
        ]);

        $isActive = $assessmentType->is_active;

        return back()->with('toast', [
            'type' => 'success',
            'title' => $isActive ? 'Jenis Penilaian Diaktifkan' : 'Jenis Penilaian Dinonaktifkan',
            'description' => "Jenis penilaian \"{$assessmentType->name}\" sekarang ".($isActive ? 'aktif' : 'tidak aktif').'.',
        ]);
    }

    /**
     * Remove the specified assessment type.
     */
    public function destroy(Request $request, AssessmentType $assessmentType): RedirectResponse
    {
        $this->authorizeAssessmentManage($request->user());
        $this->authorizeRecordAccess($request->user(), $assessmentType);

        $name = $assessmentType->name;

        $assessmentType->delete();

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Berhasil Dihapus',
            'description' => "Jenis penilaian \"{$name}\" telah dihapus.",
        ]);
    }

    /**
     * Determine whether the user may access the assessment type module.
     */
    private function authorizeAssessmentAccess(?User $user): void
    {
        if ($user === null) {
            abort(403);
        }

        if (! $user->isAdministrator() && ! $user->isHeadmaster() && ! $user->isSchoolAdmin()) {
            abort(403);
        }
    }

    /**
     * Determine whether the user may make changes to assessment type data.
     */
    private function authorizeAssessmentManage(User $user): void
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
     * Determine whether the user may access the given assessment type.
     */
    private function authorizeRecordAccess(User $user, AssessmentType $assessmentType): void
    {
        $this->authorizeSchoolAccess($user, (int) $assessmentType->school_id);
    }

    /**
     * Transform an assessment type into its frontend representation.
     *
     * @return array{id: int, school_id: int|null, school_name: string|null, school_code: string|null, code: string, name: string, description: string|null, weight: float, is_active: bool}
     */
    private function presentAssessmentType(AssessmentType $assessmentType): array
    {
        return [
            'id' => $assessmentType->id,
            'school_id' => $assessmentType->school_id,
            'school_name' => $assessmentType->school?->name,
            'school_code' => $assessmentType->school?->code,
            'code' => $assessmentType->code,
            'name' => $assessmentType->name,
            'description' => $assessmentType->description,
            'weight' => (float) $assessmentType->weight,
            'is_active' => (bool) $assessmentType->is_active,
        ];
    }
}
