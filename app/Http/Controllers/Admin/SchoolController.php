<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\School\StoreSchoolRequest;
use App\Http\Requests\Admin\School\UpdateSchoolRequest;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SchoolController extends Controller
{
    /**
     * Display a paginated listing of schools.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', School::class);

        $user = $request->user();
        $search = trim((string) $request->string('search'));

        $query = School::query()
            ->with([
                'headmaster:id,name,username',
                'admin:id,name,username',
            ]);

        if (! $user->isAdministrator()) {
            $query->where(function ($query) use ($user): void {
                $query->where('headmaster_id', $user->id)
                    ->orWhere('admin_id', $user->id);
            });
        }

        $schools = $query
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhereHas('headmaster', function ($query) use ($search): void {
                            $query->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('admin', function ($query) use ($search): void {
                            $query->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('is_active')
            ->latest()
            ->paginate(5)
            ->withQueryString()
            ->through(fn (School $school): array => $this->presentSchool($school, $user));

        $statsQuery = School::query();
        if (! $user->isAdministrator()) {
            $statsQuery->where(function ($query) use ($user): void {
                $query->where('headmaster_id', $user->id)
                    ->orWhere('admin_id', $user->id);
            });
        }

        $teachers = $user->isAdministrator()
            ? User::query()
                ->where('role', UserRole::Guru->value)
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get(['id', 'name', 'username'])
                ->map(fn (User $user): array => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                ])
                ->all()
            : [];

        return Inertia::render('Schools/Index', [
            'title' => 'Manajemen Sekolah',
            'schools' => $schools,
            'filters' => [
                'search' => $search,
            ],
            'teachers' => $teachers,
            'permissions' => [
                'can_create' => $user->can('create', School::class),
            ],
            'stats' => [
                'total' => (clone $statsQuery)->count(),
                'active' => (clone $statsQuery)->where('is_active', true)->count(),
                'inactive' => (clone $statsQuery)->where('is_active', false)->count(),
            ],
        ]);
    }

    /**
     * Store a newly created school.
     */
    public function store(StoreSchoolRequest $request): RedirectResponse
    {
        Gate::authorize('create', School::class);

        $school = DB::transaction(
            fn (): School => School::create([
                ...$request->validated(),
                'is_active' => false,
            ])
        );

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Berhasil Ditambahkan',
            'description' => "Sekolah \"{$school->name}\" (kode {$school->code}) telah ditambahkan.",
        ]);
    }

    /**
     * Update the specified school.
     */
    public function update(UpdateSchoolRequest $request, School $school): RedirectResponse
    {
        Gate::authorize('update', $school);

        $user = $request->user();
        $data = $request->validated();

        $updateData = [
            'name' => $data['name'] ?? $school->name,
            'address' => array_key_exists('address', $data) ? $data['address'] : $school->address,
        ];

        // Hanya administrator yang dapat mengubah kode, kepala sekolah, atau admin sekolah
        if ($user->isAdministrator()) {
            $updateData['code'] = $data['code'] ?? $school->code;

            if (array_key_exists('headmaster_id', $data)) {
                $updateData['headmaster_id'] = $data['headmaster_id'];
            }

            if (array_key_exists('admin_id', $data)) {
                $updateData['admin_id'] = $data['admin_id'];
            }
        }

        $school->update($updateData);

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Berhasil Diperbarui',
            'description' => "Sekolah \"{$school->name}\" (kode {$school->code}) telah diperbarui.",
        ]);
    }

    /**
     * Toggle the active status of the specified school.
     * Lebih dari satu sekolah boleh aktif sekaligus.
     */
    public function toggleStatus(School $school): RedirectResponse
    {
        Gate::authorize('toggleStatus', $school);

        $school->update([
            'is_active' => ! $school->is_active,
        ]);

        $isActive = (bool) $school->is_active;

        return back()->with('toast', [
            'type' => 'success',
            'title' => $isActive ? 'Sekolah Diaktifkan' : 'Sekolah Dinonaktifkan',
            'description' => "Sekolah \"{$school->name}\" sekarang ".($isActive ? 'aktif' : 'tidak aktif').'.',
        ]);
    }

    /**
     * Remove the specified school.
     */
    public function destroy(School $school): RedirectResponse
    {
        Gate::authorize('delete', $school);

        if ($school->is_active) {
            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Tidak Dapat Dihapus',
                'description' => "Sekolah \"{$school->name}\" masih aktif. Nonaktifkan terlebih dahulu sebelum menghapus.",
            ]);
        }

        $name = $school->name;

        $school->delete();

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Berhasil Dihapus',
            'description' => "Sekolah \"{$name}\" telah dihapus.",
        ]);
    }

    /**
     * Transform a school into its frontend representation.
     *
     * @return array{id: int, code: string, name: string, address: string|null, headmaster_id: int|null, admin_id: int|null, headmaster_name: string|null, admin_name: string|null, is_active: bool, can_edit: bool, can_delete: bool, can_toggle_status: bool}
     */
    private function presentSchool(School $school, User $user): array
    {
        return [
            'id' => $school->id,
            'code' => $school->code,
            'name' => $school->name,
            'address' => $school->address,
            'headmaster_id' => $school->headmaster_id,
            'admin_id' => $school->admin_id,
            'headmaster_name' => $school->headmaster?->name,
            'admin_name' => $school->admin?->name,
            'is_active' => (bool) $school->is_active,
            'can_edit' => $user->can('update', $school),
            'can_delete' => $user->can('delete', $school),
            'can_toggle_status' => $user->can('toggleStatus', $school),
        ];
    }
}
