<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Gender;
use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkGraduateStudentsRequest;
use App\Http\Requests\Admin\ImportStudentsRequest;
use App\Http\Requests\Admin\StoreStudentRequest;
use App\Http\Requests\Admin\UpdateStudentRequest;
use App\Imports\StudentsImport;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentRegistration;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class StudentController extends Controller
{
    /**
     * Display a paginated listing of students.
     */
    public function index(Request $request): Response
    {
        $this->authorizeStudentAccess($request->user());

        $scope = match (true) {
            $request->routeIs('admin.students.active') => 'active',
            $request->routeIs('admin.students.alumni') => 'alumni',
            default => 'all',
        };

        $user = $request->user();
        $accessibleSchoolIds = $user->accessibleSchoolIds();
        $activeAcademicYearId = $this->activeAcademicYearId();

        $search = trim((string) $request->string('search'));
        $schoolFilter = $request->integer('school_id');
        $classFilter = $request->integer('class_id');
        $statusFilter = RegistrationStatus::tryFrom((string) $request->string('status'));
        $genderFilter = Gender::tryFrom((string) $request->string('gender'));

        $query = Student::query()
            ->when(! $user->isAdministrator(), function ($query) use ($accessibleSchoolIds): void {
                $query->whereHas('registrations', function ($query) use ($accessibleSchoolIds): void {
                    $query->whereIn('school_id', $accessibleSchoolIds);
                });
            })
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('nisn', 'like', "%{$search}%")
                        ->orWhereHas('registrations', function ($query) use ($search): void {
                            $query->where('nis', 'like', "%{$search}%");
                        });
                });
            })
            ->when($schoolFilter > 0, function ($query) use ($schoolFilter): void {
                $query->whereHas('registrations', function ($query) use ($schoolFilter): void {
                    $query->where('school_id', $schoolFilter);
                });
            })
            ->when($classFilter > 0, function ($query) use ($classFilter): void {
                $query->whereHas('classMembers', function ($query) use ($classFilter): void {
                    $query->where('class_id', $classFilter);
                });
            })
            ->when($genderFilter !== null, function ($query) use ($genderFilter): void {
                $query->where('gender', $genderFilter->value);
            })
            ->when($statusFilter !== null, function ($query) use ($statusFilter): void {
                $query->whereHas('registrations', function ($query) use ($statusFilter): void {
                    $query->where('status', $statusFilter->value);
                });
            });

        $totalStudents = (clone $query)->count();

        $activeStudents = (clone $query)
            ->whereHas('registrations', function ($query): void {
                $query->where('status', RegistrationStatus::Aktif->value);
            })
            ->count();

        $query
            ->when($scope === 'active', function ($query): void {
                $query->whereHas('registrations', function ($query): void {
                    $query->where('status', RegistrationStatus::Aktif->value);
                });
            })
            ->when($scope === 'alumni', function ($query): void {
                $query->whereDoesntHave('registrations', function ($query): void {
                    $query->where('status', RegistrationStatus::Aktif->value);
                });
            });

        $students = $query
            ->with([
                'registrations' => fn ($query) => $query
                    ->with([
                        'school:id,name,code',
                        'entryAcademicYear:id,code,name',
                        'exitAcademicYear:id,code,name',
                    ])
                    ->orderByDesc('id'),
                'classMembers' => fn ($query) => $query
                    ->where('academic_year_id', $activeAcademicYearId)
                    ->with('schoolClass:id,code,name'),
            ])
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Student $student): array => $this->presentStudent($student));

        $schools = School::query()
            ->whereIn('id', $accessibleSchoolIds)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'is_active']);

        $classes = SchoolClass::query()
            ->whereIn('school_id', $accessibleSchoolIds)
            ->where('academic_year_id', $activeAcademicYearId)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        return Inertia::render('Students/Index', [
            'title' => match ($scope) {
                'active' => 'Siswa Aktif',
                'alumni' => 'Alumni / Non Aktif',
                default => 'Manajemen Siswa',
            },
            'scope' => $scope,
            'students' => $students,
            'filters' => [
                'search' => $search,
                'school_id' => $schoolFilter ?: null,
                'class_id' => $classFilter ?: null,
                'status' => $statusFilter?->value,
                'gender' => $genderFilter?->value,
            ],
            'schools' => $schools,
            'classes' => $classes,
            'genders' => $this->genderOptions(),
            'statuses' => $this->statusOptions($scope),
            'academicYears' => $this->academicYearOptions(),
            'stats' => [
                'total' => $totalStudents,
                'active' => $activeStudents,
                'inactive' => $totalStudents - $activeStudents,
            ],
        ]);
    }

    /**
     * Display a listing containing only students with an active registration.
     */
    public function active(Request $request): Response
    {
        return $this->index($request);
    }

    /**
     * Display a listing containing only students without an active registration.
     */
    public function alumni(Request $request): Response
    {
        return $this->index($request);
    }

    /**
     * Mark the active registrations of the selected students as graduated.
     */
    public function bulkGraduate(BulkGraduateStudentsRequest $request): RedirectResponse
    {
        $this->authorizeStudentManage($request->user());

        $user = $request->user();
        $studentIds = $request->validated('student_ids');

        $query = StudentRegistration::query()
            ->whereIn('student_id', $studentIds)
            ->where('status', RegistrationStatus::Aktif->value);

        if (! $user->isAdministrator()) {
            $query->whereIn('school_id', $user->accessibleSchoolIds());
        }

        $exitAcademicYearId = $this->activeAcademicYearId();

        $updated = $query->update([
            'status' => RegistrationStatus::Lulus->value,
            'exit_academic_year_id' => $exitAcademicYearId > 0 ? $exitAcademicYearId : null,
        ]);

        if ($updated === 0) {
            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Tidak Ada Registrasi Aktif',
                'description' => 'Siswa terpilih tidak memiliki registrasi aktif yang dapat diluluskan.',
            ]);
        }

        $skipped = count($studentIds) - $updated;

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Siswa Diluluskan',
            'description' => $skipped > 0
                ? "{$updated} siswa berhasil diluluskan. {$skipped} siswa dilewati karena tidak memiliki registrasi aktif yang dapat diakses."
                : "{$updated} siswa berhasil diluluskan.",
        ]);
    }

    /**
     * Store a newly created student with its first registration.
     */
    public function store(StoreStudentRequest $request): RedirectResponse
    {
        $this->authorizeStudentManage($request->user());

        $activeAcademicYear = AcademicYear::query()->where('is_active', true)->first();

        if ($activeAcademicYear === null) {
            return back()
                ->withErrors(['school_id' => 'Tahun pelajaran aktif belum diatur.'])
                ->with('toast', [
                    'type' => 'error',
                    'title' => 'Tahun Pelajaran Belum Aktif',
                    'description' => 'Aktifkan tahun pelajaran terlebih dahulu sebelum menambahkan siswa.',
                ])
                ->withInput();
        }

        $photoPath = null;

        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('students/photos', 'public');
        }

        try {
            $student = DB::transaction(function () use ($request, $photoPath, $activeAcademicYear): Student {
                $student = Student::create([
                    'nisn' => $request->validated('nisn') ?: null,
                    'name' => trim((string) $request->validated('name')),
                    'gender' => $request->validated('gender'),
                    'birth_place' => $request->validated('birth_place') ?: null,
                    'birth_date' => $request->validated('birth_date') ?: null,
                    'photo_path' => $photoPath,
                    'father_name' => $request->validated('father_name') ?: null,
                    'father_occupation' => $request->validated('father_occupation') ?: null,
                    'father_phone' => $request->validated('father_phone') ?: null,
                    'mother_name' => $request->validated('mother_name') ?: null,
                    'mother_occupation' => $request->validated('mother_occupation') ?: null,
                    'mother_phone' => $request->validated('mother_phone') ?: null,
                    'parent_address' => $request->validated('parent_address') ?: null,
                    'parent_bio' => $request->validated('parent_bio') ?: null,
                ]);

                StudentRegistration::create([
                    'student_id' => $student->id,
                    'school_id' => $request->validated('school_id'),
                    'nis' => strtoupper(trim((string) $request->validated('nis'))),
                    'entry_academic_year_id' => $activeAcademicYear->id,
                    'exit_academic_year_id' => $request->validated('status') === RegistrationStatus::Aktif->value
                        ? null
                        : $request->validated('exit_academic_year_id'),
                    'status' => $request->validated('status'),
                    'registered_at' => now()->toDateString(),
                ]);

                return $student;
            });
        } catch (UniqueConstraintViolationException) {
            if ($photoPath !== null) {
                Storage::disk('public')->delete($photoPath);
            }

            return back()
                ->withErrors(['nis' => 'NIS sudah digunakan di sekolah tersebut.'])
                ->withInput();
        }

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Berhasil Ditambahkan',
            'description' => "Siswa \"{$student->name}\" telah ditambahkan beserta registrasinya.",
        ]);
    }

    /**
     * Import students and their registrations from an uploaded spreadsheet.
     */
    public function import(ImportStudentsRequest $request): RedirectResponse
    {
        $this->authorizeStudentManage($request->user());

        $activeAcademicYear = AcademicYear::query()->where('is_active', true)->first();

        if ($activeAcademicYear === null) {
            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Tahun Pelajaran Belum Aktif',
                'description' => 'Aktifkan tahun pelajaran terlebih dahulu sebelum mengimpor siswa.',
            ]);
        }

        $import = new StudentsImport(
            (int) $request->validated('school_id'),
            $activeAcademicYear->id,
        );

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
                'description' => $import->errors[0] ?? 'Berkas tidak memuat data siswa.',
            ]);
        }

        $description = "{$import->imported} siswa berhasil diimpor.";

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
     * Update the specified student.
     */
    public function update(UpdateStudentRequest $request, Student $student): RedirectResponse
    {
        $this->authorizeStudentRecordManage($request->user(), $student);

        $oldPhotoPath = $student->photo_path;
        $photoPath = $oldPhotoPath;

        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('students/photos', 'public');
        }

        $student->update([
            'nisn' => $request->validated('nisn') ?: null,
            'name' => trim((string) $request->validated('name')),
            'gender' => $request->validated('gender'),
            'birth_place' => $request->validated('birth_place') ?: null,
            'birth_date' => $request->validated('birth_date') ?: null,
            'photo_path' => $photoPath,
            'father_name' => $request->validated('father_name') ?: null,
            'father_occupation' => $request->validated('father_occupation') ?: null,
            'father_phone' => $request->validated('father_phone') ?: null,
            'mother_name' => $request->validated('mother_name') ?: null,
            'mother_occupation' => $request->validated('mother_occupation') ?: null,
            'mother_phone' => $request->validated('mother_phone') ?: null,
            'parent_address' => $request->validated('parent_address') ?: null,
            'parent_bio' => $request->validated('parent_bio') ?: null,
        ]);

        if ($oldPhotoPath !== null && $oldPhotoPath !== $photoPath) {
            Storage::disk('public')->delete($oldPhotoPath);
        }

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Berhasil Diperbarui',
            'description' => "Data siswa \"{$student->fresh()->name}\" telah diperbarui.",
        ]);
    }

    /**
     * Remove the specified student (soft delete).
     */
    public function destroy(Request $request, Student $student): RedirectResponse
    {
        $this->authorizeStudentRecordManage($request->user(), $student);

        $hasActiveRegistration = $student->registrations()
            ->where('status', RegistrationStatus::Aktif->value)
            ->exists();

        if ($hasActiveRegistration) {
            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Tidak Dapat Dihapus',
                'description' => 'Siswa masih memiliki registrasi aktif. Ubah status registrasinya terlebih dahulu.',
            ]);
        }

        if ($student->classMembers()->exists()) {
            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Tidak Dapat Dihapus',
                'description' => 'Siswa masih tercatat sebagai anggota kelas. Keluarkan dari kelas terlebih dahulu.',
            ]);
        }

        $name = $student->name;

        $student->delete();

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Berhasil Dihapus',
            'description' => "Siswa \"{$name}\" telah dihapus.",
        ]);
    }

    /**
     * Determine whether the user may access the student module.
     */
    private function authorizeStudentAccess(?User $user): void
    {
        if ($user === null) {
            abort(403);
        }

        if ($user->isAdministrator() || $user->isSchoolAdmin() || $user->isHeadmaster()) {
            return;
        }

        abort(403);
    }

    /**
     * Determine whether the user may make changes to students.
     */
    private function authorizeStudentManage(User $user): void
    {
        if ($user->isAdministrator() || $user->isSchoolAdmin()) {
            return;
        }

        abort(403);
    }

    /**
     * Determine whether the user may manage the given student.
     */
    private function authorizeStudentRecordManage(User $user, Student $student): void
    {
        $this->authorizeStudentManage($user);

        if ($user->isAdministrator()) {
            return;
        }

        $hasAccess = $student->registrations()
            ->whereIn('school_id', $user->accessibleSchoolIds())
            ->exists();

        if (! $hasAccess) {
            abort(403);
        }
    }

    /**
     * Transform a student into its frontend representation.
     *
     * @return array<string, mixed>
     */
    private function presentStudent(Student $student): array
    {
        $registrations = $student->registrations
            ->map(fn (StudentRegistration $registration): array => [
                'id' => $registration->id,
                'student_id' => $registration->student_id,
                'school_id' => $registration->school_id,
                'school_name' => $registration->school?->name,
                'school_code' => $registration->school?->code,
                'nis' => $registration->nis,
                'entry_academic_year_id' => $registration->entry_academic_year_id,
                'entry_academic_year_name' => $registration->entryAcademicYear?->name,
                'exit_academic_year_id' => $registration->exit_academic_year_id,
                'exit_academic_year_name' => $registration->exitAcademicYear?->name,
                'status' => $registration->status->value,
                'status_label' => $registration->status->label(),
                'registered_at' => $registration->registered_at?->toDateString(),
            ])
            ->values();

        $registration = $registrations->firstWhere('status', RegistrationStatus::Aktif->value)
            ?? $registrations->first();

        $classMember = $student->classMembers->first();

        return [
            'id' => $student->id,
            'nisn' => $student->nisn,
            'name' => $student->name,
            'gender' => $student->gender->value,
            'gender_label' => $student->gender->label(),
            'birth_place' => $student->birth_place,
            'birth_date' => $student->birth_date?->toDateString(),
            'photo_url' => $student->photo_path ? Storage::disk('public')->url($student->photo_path) : null,
            'father_name' => $student->father_name,
            'father_occupation' => $student->father_occupation,
            'father_phone' => $student->father_phone,
            'mother_name' => $student->mother_name,
            'mother_occupation' => $student->mother_occupation,
            'mother_phone' => $student->mother_phone,
            'parent_address' => $student->parent_address,
            'parent_bio' => $student->parent_bio,
            'registrations' => $registrations->all(),
            'registration' => $registration,
            'current_class' => $classMember ? [
                'id' => $classMember->schoolClass?->id,
                'code' => $classMember->schoolClass?->code,
                'name' => $classMember->schoolClass?->name,
            ] : null,
            'is_active' => $registrations->contains('status', RegistrationStatus::Aktif->value),
        ];
    }

    /**
     * Get the active academic year identifier.
     */
    private function activeAcademicYearId(): int
    {
        return (int) (AcademicYear::query()
            ->where('is_active', true)
            ->value('id')
            ?? AcademicYear::query()->orderByDesc('code')->value('id'));
    }

    /**
     * Get the academic year options for the entry year selector.
     *
     * @return array<int, array<string, mixed>>
     */
    private function academicYearOptions(): array
    {
        return AcademicYear::query()
            ->orderByDesc('is_active')
            ->orderByDesc('code')
            ->get(['id', 'code', 'name', 'semester', 'is_active'])
            ->map(fn (AcademicYear $academicYear): array => [
                'id' => $academicYear->id,
                'code' => $academicYear->code,
                'name' => $academicYear->name,
                'semester' => $academicYear->semester?->value,
                'semester_label' => $academicYear->semester?->label(),
                'is_active' => (bool) $academicYear->is_active,
            ])
            ->all();
    }

    /**
     * Get the gender options.
     *
     * @return array<int, array{value: string, label: string}>
     */
    private function genderOptions(): array
    {
        return array_map(
            fn (Gender $gender): array => [
                'value' => $gender->value,
                'label' => $gender->label(),
            ],
            Gender::cases()
        );
    }

    /**
     * Get the registration status options for the current listing scope.
     *
     * @return array<int, array{value: string, label: string}>
     */
    private function statusOptions(string $scope = 'all'): array
    {
        $statuses = match ($scope) {
            'active' => [RegistrationStatus::Aktif],
            'alumni' => array_values(array_filter(
                RegistrationStatus::cases(),
                fn (RegistrationStatus $status): bool => $status !== RegistrationStatus::Aktif
            )),
            default => RegistrationStatus::cases(),
        };

        return array_map(
            fn (RegistrationStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ],
            $statuses
        );
    }
}
