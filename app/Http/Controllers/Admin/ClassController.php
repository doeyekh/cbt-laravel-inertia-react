<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreClassRequest;
use App\Http\Requests\Admin\UpdateClassRequest;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ClassController extends Controller
{
    /**
     * Display a paginated listing of classes.
     */
    public function index(Request $request): Response
    {
        $this->authorizeClassAccess($request->user());

        $accessibleSchoolIds = $request->user()->accessibleSchoolIds();

        $search = trim((string) $request->string('search'));

        $activeAcademicYearId = (int) (AcademicYear::query()
            ->where('is_active', true)
            ->value('id')
            ?? AcademicYear::query()->orderByDesc('code')->value('id'));

        $classes = SchoolClass::query()
            ->whereIn('school_id', $accessibleSchoolIds)
            ->where('academic_year_id', $activeAcademicYearId)
            ->with(['school', 'academicYear'])
            ->withCount('classMembers')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhereHas('school', function ($query) use ($search): void {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('code', 'like', "%{$search}%");
                        })
                        ->orWhereHas('academicYear', function ($query) use ($search): void {
                            $query->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('is_active')
            ->latest()
            ->paginate(5)
            ->withQueryString()
            ->through(fn (SchoolClass $schoolClass): array => $this->presentClass($schoolClass));

        $totalClasses = SchoolClass::query()
            ->whereIn('school_id', $accessibleSchoolIds)
            ->where('academic_year_id', $activeAcademicYearId)
            ->count();

        $activeCount = SchoolClass::query()
            ->whereIn('school_id', $accessibleSchoolIds)
            ->where('academic_year_id', $activeAcademicYearId)
            ->where('is_active', true)
            ->count();

        $academicYears = AcademicYear::query()
            ->orderByDesc('is_active')
            ->orderByDesc('code')
            ->get(['id', 'code', 'name', 'semester', 'is_active']);

        $schools = School::query()
            ->whereIn('id', $accessibleSchoolIds)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'is_active']);

        return Inertia::render('Classes/Index', [
            'title' => 'Manajemen Kelas',
            'classes' => $classes,
            'filters' => [
                'search' => $search,
            ],
            'schools' => $schools,
            'academicYears' => $academicYears,
            'stats' => [
                'total' => $totalClasses,
                'active' => $activeCount,
                'inactive' => $totalClasses - $activeCount,
            ],
        ]);
    }

    /**
     * Store a newly created class.
     */
    public function store(StoreClassRequest $request): RedirectResponse
    {
        $this->authorizeClassManage($request->user());

        $academicYearId = (int) (AcademicYear::query()
            ->where('is_active', true)
            ->value('id')
            ?? AcademicYear::query()->orderByDesc('code')->value('id'));

        $schoolClass = DB::transaction(
            fn (): SchoolClass => SchoolClass::create([
                'school_id' => $request->schoolId(),
                'academic_year_id' => $academicYearId,
                'code' => strtoupper((string) $request->validated('code')),
                'name' => trim((string) $request->validated('name')),
                'is_active' => false,
            ])
        );

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Berhasil Ditambahkan',
            'description' => "Kelas \"{$schoolClass->name}\" (kode {$schoolClass->code}) telah ditambahkan.",
        ]);
    }

    /**
     * Update the specified class.
     */
    public function update(UpdateClassRequest $request, SchoolClass $schoolClass): RedirectResponse
    {
        $this->authorizeClassManage($request->user());

        $schoolClass->update([
            'code' => strtoupper((string) $request->validated('code')),
            'name' => trim((string) $request->validated('name')),
        ]);

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Berhasil Diperbarui',
            'description' => "Kelas \"{$schoolClass->fresh()->name}\" (kode {$schoolClass->fresh()->code}) telah diperbarui.",
        ]);
    }

    /**
     * Toggle the active status of the specified class.
     */
    public function toggleStatus(Request $request, SchoolClass $schoolClass): RedirectResponse
    {
        $this->authorizeClassManage($request->user());

        $wasActive = $schoolClass->is_active;

        $schoolClass->update([
            'is_active' => ! $wasActive,
        ]);

        $isActive = $schoolClass->fresh()->is_active;

        return back()->with('toast', [
            'type' => 'success',
            'title' => $isActive ? 'Kelas Diaktifkan' : 'Kelas Dinonaktifkan',
            'description' => "Kelas \"{$schoolClass->fresh()->name}\" sekarang ".($isActive ? 'aktif' : 'tidak aktif').'.',
        ]);
    }

    /**
     * Remove the specified class.
     */
    public function destroy(Request $request, SchoolClass $schoolClass): RedirectResponse
    {
        $this->authorizeClassManage($request->user());

        if ($schoolClass->is_active) {
            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Tidak Dapat Dihapus',
                'description' => "Kelas \"{$schoolClass->name}\" masih aktif. Nonaktifkan terlebih dahulu sebelum menghapusnya.",
            ]);
        }

        $name = $schoolClass->name;

        $schoolClass->delete();

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Berhasil Dihapus',
            'description' => "Kelas \"{$name}\" telah dihapus.",
        ]);
    }

    /**
     * Determine whether the user may access the class module.
     */
    private function authorizeClassAccess(?User $user): void
    {
        if ($user === null) {
            abort(403);
        }

        if (! $user->isAdministrator() && ! $user->isHeadmaster() && ! $user->isSchoolAdmin()) {
            abort(403);
        }
    }

    /**
     * Determine whether the user may make changes to classes.
     * Kepala sekolah (headmaster) hanya boleh melihat.
     */
    private function authorizeClassManage(User $user): void
    {
        if ($user->isAdministrator() || $user->isSchoolAdmin()) {
            return;
        }

        abort(403);
    }

    /**
     * Transform a class into its frontend representation.
     *
     * @return array<string, mixed>
     */
    private function presentClass(SchoolClass $schoolClass): array
    {
        return [
            'id' => $schoolClass->id,
            'code' => $schoolClass->code,
            'name' => $schoolClass->name,
            'school_id' => $schoolClass->school_id,
            'school_name' => $schoolClass->school?->name,
            'school_code' => $schoolClass->school?->code,
            'academic_year_id' => $schoolClass->academic_year_id,
            'academic_year_code' => $schoolClass->academicYear?->code,
            'academic_year_name' => $schoolClass->academicYear?->name,
            'academic_year_semester' => $schoolClass->academicYear?->semester?->value,
            'academic_year_semester_label' => $schoolClass->academicYear?->semester?->label(),
            'students_count' => (int) ($schoolClass->class_members_count ?? 0),
            'is_active' => (bool) $schoolClass->is_active,
        ];
    }
}
