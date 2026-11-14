<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Imports\UsersImport;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class UserController extends Controller
{
    /**
     * Display a paginated listing of users.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));

        $users = User::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(5)
            ->withQueryString();

        return Inertia::render('Users/Index', [
            'title' => 'Manajemen Pengguna',
            'users' => $users,
            'filters' => [
                'search' => $search,
            ],
            'roles' => array_map(
                fn (UserRole $role): array => [
                    'value' => $role->value,
                    'label' => $role->label(),
                ],
                UserRole::cases()
            ),
            'stats' => [
                'total' => User::count(),
                'active' => User::where('is_active', true)->count(),
                'inactive' => User::where('is_active', false)->count(),
            ],
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = DB::transaction(
            fn (): User => User::create([
                ...$data,
                'password' => Hash::make($data['password']),
                'is_active' => true,
            ])
        );

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Berhasil Ditambahkan',
            'description' => "Pengguna \"{$user->name}\" (username {$user->username}) telah ditambahkan.",
        ]);
    }

    /**
     * Import users from an uploaded spreadsheet.
     *
     * Password defaults to "password" and role defaults to "guru" when the
     * corresponding column is empty or omitted.
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'extensions:xlsx,xls,csv', 'max:5120'],
        ], [
            'file.required' => 'Berkas import wajib dipilih.',
            'file.file' => 'Berkas import tidak valid.',
            'file.extensions' => 'Berkas harus berformat XLSX, XLS, atau CSV.',
            'file.max' => 'Ukuran berkas maksimal 5 MB.',
        ]);

        $import = new UsersImport;

        try {
            Excel::import($import, $request->file('file'));
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Import Gagal',
                'description' => 'Berkas tidak dapat dibaca. Pastikan format dan isi berkas sudah benar.',
            ]);
        }

        if ($import->imported === 0) {
            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Tidak Ada Data Diimpor',
                'description' => $import->errors[0] ?? 'Berkas tidak memuat data pengguna.',
            ]);
        }

        $description = "{$import->imported} pengguna berhasil diimpor.";

        if ($import->skipped > 0) {
            $description .= " {$import->skipped} baris dilewati. ".$import->errors[0];
        }

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Import Selesai',
            'description' => $description,
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        $updateData = [
            'name' => $data['name'] ?? $user->name,
            'username' => $data['username'] ?? $user->username,
            'email' => $data['email'] ?? $user->email,
            'role' => $data['role'] ?? $user->role,
        ];

        if ($data['password'] ?? null) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $user->update($updateData);

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Berhasil Diperbarui',
            'description' => "Pengguna \"{$user->name}\" (username {$user->username}) telah diperbarui.",
        ]);
    }

    /**
     * Toggle the active status of the specified user.
     */
    public function toggleStatus(int $userId): RedirectResponse
    {
        $user = User::findOrFail($userId);

        $user->update([
            'is_active' => ! $user->is_active,
        ]);

        $isActive = (bool) $user->is_active;

        return back()->with('toast', [
            'type' => 'success',
            'title' => $isActive ? 'Pengguna Diaktifkan' : 'Pengguna Dinonaktifkan',
            'description' => "Pengguna \"{$user->name}\" (username {$user->username}) sekarang ".($isActive ? 'aktif' : 'tidak aktif').'.',
        ]);
    }

    /**
     * Remove the specified user (soft delete).
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()?->is($user)) {
            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Tidak Dapat Dihapus',
                'description' => 'Anda tidak dapat menghapus akun yang sedang digunakan.',
            ]);
        }

        $name = $user->name;

        $user->delete();

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Berhasil Dihapus',
            'description' => "Pengguna \"{$name}\" telah dihapus.",
        ]);
    }

    /**
     * Transform a user into its frontend representation.
     *
     * @return array{id: int, code: string, name: string, username: string, email: string, role: string, role_label: string, is_active: bool}
     */
    private function presentUser(User $user): array
    {
        return [
            'id' => $user->id,
            'code' => $user->username,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role->value,
            'role_label' => $user->role->label(),
            'is_active' => (bool) $user->is_active,
        ];
    }
}
