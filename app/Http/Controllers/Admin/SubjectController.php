<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSubjectRequest;
use App\Http\Requests\Admin\UpdateSubjectRequest;
use App\Imports\SubjectsImport;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class SubjectController extends Controller
{
    /**
     * Display a paginated listing of subjects.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Subject::class);

        $user = $request->user();
        $search = trim((string) $request->string('search'));

        $subjectsPaginator = Subject::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(5)
            ->withQueryString()
            ->through(fn (Subject $subject): array => $this->presentSubject($subject, $user));

        $activeSubject = Subject::where('is_active', true)->first();

        return Inertia::render('Subjects/Index', [
            'title' => 'Manajemen Mata Pelajaran',
            'subjects' => $subjectsPaginator,
            'filters' => [
                'search' => $search,
            ],
            'stats' => [
                'total' => Subject::count(),
                'active' => $activeSubject ? $this->presentSubject($activeSubject, $user) : null,
                'inactive' => Subject::where('is_active', false)->count(),
            ],
            'permissions' => [
                'can_create' => $user->can('create', Subject::class),
            ],
        ]);
    }

    /**
     * Store a newly created subject.
     */
    public function store(StoreSubjectRequest $request): RedirectResponse
    {
        Gate::authorize('create', Subject::class);
        $subject = DB::transaction(
            fn (): Subject => Subject::create([
                ...$request->validated(),
                'is_active' => false,
            ])
        );

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Berhasil Ditambahkan',
            'description' => "Mata pelajaran \"{$subject->name}\" (kode {$subject->code}) telah ditambahkan.",
        ]);
    }

    /**
     * Import subjects from an uploaded spreadsheet.
     */
    public function import(Request $request): RedirectResponse
    {
        Gate::authorize('create', Subject::class);
        $request->validate([
            'file' => ['required', 'file', 'extensions:xlsx,xls,csv', 'max:5120'],
        ], [
            'file.required' => 'Berkas import wajib dipilih.',
            'file.file' => 'Berkas import tidak valid.',
            'file.extensions' => 'Berkas harus berformat XLSX, XLS, atau CSV.',
            'file.max' => 'Ukuran berkas maksimal 5 MB.',
        ]);

        $import = new SubjectsImport;

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
                'description' => $import->errors[0] ?? 'Berkas tidak memuat data mata pelajaran.',
            ]);
        }

        $description = "{$import->imported} mata pelajaran berhasil diimpor.";

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
     * Update the specified subject.
     */
    public function update(UpdateSubjectRequest $request, Subject $subject): RedirectResponse
    {
        Gate::authorize('update', $subject);
        $data = $request->validated();

        $subject->update([
            'code' => $data['code'] ?? $subject->code,
            'name' => $data['name'] ?? $subject->name,
        ]);

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Berhasil Diperbarui',
            'description' => "Mata pelajaran \"{$subject->name}\" (kode {$subject->code}) telah diperbarui.",
        ]);
    }

    /**
     * Toggle the active status of the specified subject.
     * Lebih dari satu mata pelajaran boleh aktif sekaligus.
     */
    public function toggleStatus(Request $request, $subjectId): RedirectResponse
    {
        $subject = Subject::findOrFail($subjectId);

        Gate::authorize('toggleStatus', $subject);

        $wasActive = $subject->is_active;

        $subject->update([
            'is_active' => ! $wasActive,
        ]);

        $isActive = $subject->is_active;

        return back()->with('toast', [
            'type' => 'success',
            'title' => $isActive ? 'Mata Pelajaran Diaktifkan' : 'Mata Pelajaran Dinonaktifkan',
            'description' => "Mata pelajaran \"{$subject->name}\" sekarang ".($isActive ? 'aktif' : 'tidak aktif').'.',
        ]);
    }

    /**
     * Remove the specified subject.
     */
    public function destroy(Subject $subject): RedirectResponse
    {
        Gate::authorize('delete', $subject);
        $name = $subject->name;

        $subject->delete();

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Berhasil Dihapus',
            'description' => "Mata pelajaran \"{$name}\" telah dihapus.",
        ]);
    }

    /**
     * Transform a subject into its frontend representation.
     *
     * @return array{id: int, code: string, name: string, is_active: bool, can_edit: bool, can_delete: bool, can_toggle_status: bool}
     */
    private function presentSubject(Subject $subject, ?User $user = null): array
    {
        return [
            'id' => $subject->id,
            'code' => $subject->code,
            'name' => $subject->name,
            'is_active' => (bool) $subject->is_active,
            'can_edit' => $user?->can('update', $subject) ?? false,
            'can_delete' => $user?->can('delete', $subject) ?? false,
            'can_toggle_status' => $user?->can('toggleStatus', $subject) ?? false,
        ];
    }
}
