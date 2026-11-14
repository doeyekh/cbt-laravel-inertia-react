<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStudentRegistrationRequest;
use App\Http\Requests\Admin\UpdateStudentRegistrationRequest;
use App\Models\AcademicYear;
use App\Models\ClassMember;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentRegistration;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class StudentRegistrationController extends Controller
{
    /**
     * Display the school registrations of the given student.
     */
    public function index(Request $request, Student $student): Response
    {
        $this->authorizeStudentRecordAccess($request->user(), $student);

        $user = $request->user();

        $registrations = $student->registrations()
            ->with([
                'school:id,name,code',
                'entryAcademicYear:id,code,name',
                'exitAcademicYear:id,code,name',
            ])
            ->orderByDesc('id')
            ->get()
            ->map(fn (StudentRegistration $registration): array => $this->presentRegistration($registration))
            ->all();

        $schools = School::query()
            ->whereIn('id', $user->accessibleSchoolIds())
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'is_active']);

        return Inertia::render('Students/Registrations', [
            'title' => 'Registrasi Siswa',
            'student' => [
                'id' => $student->id,
                'nisn' => $student->nisn,
                'name' => $student->name,
                'gender_label' => $student->gender->label(),
            ],
            'registrations' => $registrations,
            'schools' => $schools,
            'academicYears' => $this->academicYearOptions(),
            'canManage' => $user->isAdministrator() || $user->isSchoolAdmin(),
        ]);
    }

    /**
     * Search existing students that can be registered at the given school.
     */
    public function available(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null || (! $user->isAdministrator() && ! $user->isSchoolAdmin())) {
            abort(403);
        }

        $schoolId = $request->integer('school_id');

        if ($schoolId <= 0) {
            return response()->json([
                'message' => 'Sekolah wajib dipilih.',
            ], 422);
        }

        if (! in_array($schoolId, $user->accessibleSchoolIds(), true)) {
            abort(403);
        }

        $search = trim((string) $request->string('search'));

        if ($search === '') {
            return response()->json(['data' => []]);
        }

        $students = Student::query()
            ->when(! $user->isAdministrator(), function ($query) use ($user): void {
                $query->whereHas('registrations', function ($query) use ($user): void {
                    $query->whereIn('school_id', $user->accessibleSchoolIds());
                });
            })
            ->whereDoesntHave('registrations', function ($query) use ($schoolId): void {
                $query->where('school_id', $schoolId);
            })
            ->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%")
                    ->orWhereHas('registrations', function ($query) use ($search): void {
                        $query->where('nis', 'like', "%{$search}%");
                    });
            })
            ->with([
                'registrations' => fn ($query) => $query
                    ->with('school:id,name,code')
                    ->orderByDesc('id'),
            ])
            ->orderBy('name')
            ->limit(20)
            ->get();

        return response()->json([
            'data' => $students->map(function (Student $student): array {
                $registration = $student->registrations
                    ->firstWhere('status', RegistrationStatus::Aktif->value)
                    ?? $student->registrations->first();

                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'nisn' => $student->nisn,
                    'gender_label' => $student->gender->label(),
                    'photo_url' => $student->photo_path ? Storage::disk('public')->url($student->photo_path) : null,
                    'current_nis' => $registration?->nis,
                    'current_school_name' => $registration?->school?->name,
                    'current_school_code' => $registration?->school?->code,
                    'current_status' => $registration?->status->value,
                    'current_status_label' => $registration?->status->label(),
                    'has_active_registration' => $student->registrations
                        ->contains('status', RegistrationStatus::Aktif),
                ];
            })->values()->all(),
        ]);
    }

    /**
     * Store a new school registration for the student.
     */
    public function store(StoreStudentRegistrationRequest $request, Student $student): RedirectResponse
    {
        $this->authorizeStudentRecordManage($request->user(), $student);

        $activeAcademicYear = AcademicYear::query()->where('is_active', true)->first();

        if ($activeAcademicYear === null) {
            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Tahun Pelajaran Belum Aktif',
                'description' => 'Aktifkan tahun pelajaran terlebih dahulu sebelum menambah registrasi.',
            ]);
        }

        try {
            DB::transaction(function () use ($request, $student, $activeAcademicYear): void {
                $status = RegistrationStatus::from((string) $request->validated('status'));

                if ($status === RegistrationStatus::Aktif) {
                    $this->deactivateOtherRegistrations($student);
                }

                StudentRegistration::create([
                    'student_id' => $student->id,
                    'school_id' => $request->validated('school_id'),
                    'nis' => strtoupper(trim((string) $request->validated('nis'))),
                    'entry_academic_year_id' => $activeAcademicYear->id,
                    'exit_academic_year_id' => $status === RegistrationStatus::Aktif
                        ? null
                        : $request->validated('exit_academic_year_id'),
                    'status' => $status,
                    'registered_at' => now()->toDateString(),
                ]);
            });
        } catch (UniqueConstraintViolationException) {
            return back()
                ->withErrors(['school_id' => 'Data registrasi siswa tersebut sudah ada.'])
                ->withInput();
        }

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Registrasi Ditambahkan',
            'description' => "Registrasi sekolah untuk \"{$student->name}\" telah ditambahkan.",
        ]);
    }

    /**
     * Update the specified school registration.
     */
    public function update(UpdateStudentRegistrationRequest $request, Student $student, StudentRegistration $registration): RedirectResponse
    {
        $this->ensureRegistrationBelongsToStudent($registration, $student);
        $this->authorizeStudentRecordManage($request->user(), $student);

        try {
            DB::transaction(function () use ($request, $student, $registration): void {
                $status = RegistrationStatus::from((string) $request->validated('status'));

                if ($status === RegistrationStatus::Aktif) {
                    $this->deactivateOtherRegistrations($student, $registration->id);
                }

                $registration->update([
                    'school_id' => $request->validated('school_id'),
                    'nis' => strtoupper(trim((string) $request->validated('nis'))),
                    'entry_academic_year_id' => $request->validated('entry_academic_year_id'),
                    'exit_academic_year_id' => $status === RegistrationStatus::Aktif
                        ? null
                        : $request->validated('exit_academic_year_id'),
                    'status' => $status,
                ]);
            });
        } catch (UniqueConstraintViolationException) {
            return back()
                ->withErrors(['school_id' => 'Data registrasi siswa tersebut sudah ada.'])
                ->withInput();
        }

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Registrasi Diperbarui',
            'description' => "Registrasi sekolah untuk \"{$student->name}\" telah diperbarui.",
        ]);
    }

    /**
     * Remove the specified school registration.
     */
    public function destroy(Request $request, Student $student, StudentRegistration $registration): RedirectResponse
    {
        $this->ensureRegistrationBelongsToStudent($registration, $student);
        $this->authorizeStudentRecordManage($request->user(), $student);

        if ($registration->status === RegistrationStatus::Aktif) {
            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Tidak Dapat Dihapus',
                'description' => 'Registrasi masih aktif. Ubah statusnya terlebih dahulu sebelum menghapus.',
            ]);
        }

        $hasClassMember = ClassMember::query()
            ->where('student_id', $student->id)
            ->whereHas('schoolClass', function ($query) use ($registration): void {
                $query->where('school_id', $registration->school_id);
            })
            ->exists();

        if ($hasClassMember) {
            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Tidak Dapat Dihapus',
                'description' => 'Siswa masih tercatat sebagai anggota kelas di sekolah tersebut.',
            ]);
        }

        $registration->delete();

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Registrasi Dihapus',
            'description' => "Registrasi sekolah untuk \"{$student->name}\" telah dihapus.",
        ]);
    }

    /**
     * Mark the student's other active registrations as transferred.
     */
    private function deactivateOtherRegistrations(Student $student, ?int $ignoreRegistrationId = null): void
    {
        StudentRegistration::query()
            ->where('student_id', $student->id)
            ->where('status', RegistrationStatus::Aktif->value)
            ->when($ignoreRegistrationId !== null, fn ($query) => $query->whereKeyNot($ignoreRegistrationId))
            ->update([
                'status' => RegistrationStatus::Pindah->value,
                'exit_academic_year_id' => AcademicYear::query()
                    ->where('is_active', true)
                    ->value('id'),
            ]);
    }

    /**
     * Ensure the given registration belongs to the student in the route.
     */
    private function ensureRegistrationBelongsToStudent(StudentRegistration $registration, Student $student): void
    {
        if ((int) $registration->student_id !== (int) $student->id) {
            abort(404);
        }
    }

    /**
     * Determine whether the user may view the student's registrations.
     */
    private function authorizeStudentRecordAccess(?User $user, Student $student): void
    {
        if ($user === null) {
            abort(403);
        }

        if ($user->isAdministrator()) {
            return;
        }

        if ($user->isSchoolAdmin() || $user->isHeadmaster()) {
            $hasAccess = $student->registrations()
                ->whereIn('school_id', $user->accessibleSchoolIds())
                ->exists();

            if ($hasAccess) {
                return;
            }
        }

        abort(403);
    }

    /**
     * Determine whether the user may manage the student's registrations.
     */
    private function authorizeStudentRecordManage(User $user, Student $student): void
    {
        if ($user->isAdministrator()) {
            return;
        }

        if (! $user->isSchoolAdmin()) {
            abort(403);
        }

        $hasAccess = $student->registrations()
            ->whereIn('school_id', $user->accessibleSchoolIds())
            ->exists();

        if (! $hasAccess) {
            abort(403);
        }
    }

    /**
     * Transform a registration into its frontend representation.
     *
     * @return array<string, mixed>
     */
    private function presentRegistration(StudentRegistration $registration): array
    {
        return [
            'id' => $registration->id,
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
        ];
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
}
