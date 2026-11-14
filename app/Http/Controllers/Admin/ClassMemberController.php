<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Gender;
use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreClassMemberRequest;
use App\Models\ClassMember;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ClassMemberController extends Controller
{
    /**
     * Display the students that belong to the given class.
     */
    public function index(Request $request, SchoolClass $schoolClass): Response
    {
        $this->authorizeClassMemberAccess($request->user(), $schoolClass);

        $search = trim((string) $request->string('search'));

        $baseQuery = ClassMember::query()->where('class_id', $schoolClass->id);

        $totalMembers = (clone $baseQuery)->count();
        $maleMembers = (clone $baseQuery)
            ->whereHas('student', fn ($query) => $query->where('gender', Gender::LakiLaki->value))
            ->count();

        $members = $baseQuery
            ->with([
                'student.registrations' => fn ($query) => $query->where('school_id', $schoolClass->school_id),
            ])
            ->when($search !== '', function ($query) use ($search, $schoolClass): void {
                $query->where(function ($query) use ($search, $schoolClass): void {
                    $query->whereHas('student', function ($query) use ($search): void {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('nisn', 'like', "%{$search}%");
                    })->orWhereHas('student.registrations', function ($query) use ($search, $schoolClass): void {
                        $query->where('school_id', $schoolClass->school_id)
                            ->where('nis', 'like', "%{$search}%");
                    });
                });
            })
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (ClassMember $member): array => $this->presentMember($member));

        $schoolClass->load(['school', 'academicYear']);

        return Inertia::render('ClassMembers/Index', [
            'title' => 'Anggota Kelas',
            'schoolClass' => [
                'id' => $schoolClass->id,
                'code' => $schoolClass->code,
                'name' => $schoolClass->name,
                'school_id' => $schoolClass->school_id,
                'school_name' => $schoolClass->school?->name,
                'school_code' => $schoolClass->school?->code,
                'academic_year_id' => $schoolClass->academic_year_id,
                'academic_year_code' => $schoolClass->academicYear?->code,
                'academic_year_name' => $schoolClass->academicYear?->name,
                'academic_year_semester_label' => $schoolClass->academicYear?->semester?->label(),
            ],
            'members' => $members,
            'filters' => [
                'search' => $search,
            ],
            'canManage' => $this->canManage($request->user()),
            'stats' => [
                'total' => $totalMembers,
                'male' => $maleMembers,
                'female' => $totalMembers - $maleMembers,
            ],
        ]);
    }

    /**
     * Search students that can be added to the given class.
     */
    public function available(Request $request, SchoolClass $schoolClass): JsonResponse
    {
        $this->authorizeClassMemberAccess($request->user(), $schoolClass);

        $search = trim((string) $request->string('search'));

        if ($search === '') {
            return response()->json(['data' => []]);
        }

        $students = Student::query()
            ->whereHas('registrations', function ($query) use ($schoolClass): void {
                $query->where('school_id', $schoolClass->school_id)
                    ->where('status', RegistrationStatus::Aktif->value);
            })
            ->whereDoesntHave('classMembers', function ($query) use ($schoolClass): void {
                $query->where('academic_year_id', $schoolClass->academic_year_id);
            })
            ->where(function ($query) use ($search, $schoolClass): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%")
                    ->orWhereHas('registrations', function ($query) use ($search, $schoolClass): void {
                        $query->where('school_id', $schoolClass->school_id)
                            ->where('nis', 'like', "%{$search}%");
                    });
            })
            ->with([
                'registrations' => fn ($query) => $query
                    ->where('school_id', $schoolClass->school_id)
                    ->where('status', RegistrationStatus::Aktif->value),
            ])
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(fn (Student $student): array => [
                'id' => $student->id,
                'nisn' => $student->nisn,
                'nis' => $student->registrations->first()?->nis,
                'name' => $student->name,
                'gender' => $student->gender->value,
                'gender_label' => $student->gender->label(),
                'photo_url' => $student->photo_path ? Storage::disk('public')->url($student->photo_path) : null,
            ])
            ->values();

        return response()->json(['data' => $students]);
    }

    /**
     * Add a student to the given class.
     */
    public function store(StoreClassMemberRequest $request, SchoolClass $schoolClass): RedirectResponse
    {
        $this->authorizeClassMemberManage($request->user(), $schoolClass);

        try {
            ClassMember::create([
                'class_id' => $schoolClass->id,
                'student_id' => $request->validated('student_id'),
                'academic_year_id' => $schoolClass->academic_year_id,
            ]);
        } catch (UniqueConstraintViolationException) {
            return back()
                ->withErrors(['student_id' => 'Siswa sudah terdaftar di kelas lain pada tahun pelajaran ini.'])
                ->withInput();
        }

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Siswa Ditambahkan',
            'description' => 'Siswa telah dimasukkan ke anggota kelas.',
        ]);
    }

    /**
     * Remove a student from the given class.
     */
    public function destroy(Request $request, SchoolClass $schoolClass, ClassMember $classMember): RedirectResponse
    {
        $this->authorizeClassMemberManage($request->user(), $schoolClass);

        if ((int) $classMember->class_id !== (int) $schoolClass->id) {
            abort(404);
        }

        $classMember->delete();

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Siswa Dikeluarkan',
            'description' => 'Siswa telah dikeluarkan dari anggota kelas.',
        ]);
    }

    /**
     * Determine whether the user may access the class member module.
     */
    private function authorizeClassMemberAccess(?User $user, SchoolClass $schoolClass): void
    {
        if ($user === null) {
            abort(403);
        }

        if ($user->isAdministrator()) {
            return;
        }

        if (
            ($user->isSchoolAdmin() || $user->isHeadmaster())
            && in_array((int) $schoolClass->school_id, $user->accessibleSchoolIds(), true)
        ) {
            return;
        }

        abort(403);
    }

    /**
     * Determine whether the user may manage class members.
     */
    private function authorizeClassMemberManage(User $user, SchoolClass $schoolClass): void
    {
        if ($user->isAdministrator()) {
            return;
        }

        if (
            $user->isSchoolAdmin()
            && in_array((int) $schoolClass->school_id, $user->accessibleSchoolIds(), true)
        ) {
            return;
        }

        abort(403);
    }

    /**
     * Determine whether the user may manage class members without aborting.
     */
    private function canManage(?User $user): bool
    {
        return $user !== null && ($user->isAdministrator() || $user->isSchoolAdmin());
    }

    /**
     * Transform a class member into its frontend representation.
     *
     * @return array<string, mixed>
     */
    private function presentMember(ClassMember $member): array
    {
        $student = $member->student;
        $registration = $student->registrations
            ->firstWhere('status', RegistrationStatus::Aktif)
            ?? $student->registrations->first();

        return [
            'id' => $member->id,
            'student_id' => $student->id,
            'nis' => $registration?->nis,
            'nisn' => $student->nisn,
            'name' => $student->name,
            'gender' => $student->gender->value,
            'gender_label' => $student->gender->label(),
            'photo_url' => $student->photo_path ? Storage::disk('public')->url($student->photo_path) : null,
        ];
    }
}
