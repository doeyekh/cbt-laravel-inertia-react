<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSubjectTeacherRequest;
use App\Http\Requests\Admin\UpdateSubjectTeacherRequest;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\SubjectTeacher;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubjectTeacherController extends Controller
{
    /**
     * Display a paginated listing of subject teachers for the given class.
     */
    public function index(Request $request, SchoolClass $schoolClass): Response
    {
        $this->authorizeSubjectTeacherAccess($request->user(), $schoolClass);

        $search = trim((string) $request->string('search'));

        $subjectTeachers = SubjectTeacher::query()
            ->where('school_class_id', $schoolClass->id)
            ->with(['subject', 'teacher'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->whereHas('subject', function ($query) use ($search): void {
                        $query->where('name', 'like', "%{$search}%");
                    })
                        ->orWhereHas('teacher', function ($query) use ($search): void {
                            $query->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('id')
            ->paginate(5)
            ->withQueryString()
            ->through(fn (SubjectTeacher $subjectTeacher): array => $this->presentSubjectTeacher($subjectTeacher));

        $totalSubjectTeachers = SubjectTeacher::query()
            ->where('school_class_id', $schoolClass->id)
            ->count();

        $activeCount = SubjectTeacher::query()
            ->where('school_class_id', $schoolClass->id)
            ->where('is_active', true)
            ->count();

        $subjects = Subject::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        $teachers = User::query()
            ->where('role', UserRole::Guru->value)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'school_id']);

        $schoolClass->load(['school', 'academicYear']);

        return Inertia::render('SubjectTeachers/Index', [
            'title' => 'Pembelajaran',
            'subjectTeachers' => $subjectTeachers,
            'schoolClass' => [
                'id' => $schoolClass->id,
                'code' => $schoolClass->code,
                'name' => $schoolClass->name,
                'academic_year_id' => $schoolClass->academic_year_id,
                'academic_year_code' => $schoolClass->academicYear?->code,
                'academic_year_name' => $schoolClass->academicYear?->name,
                'academic_year_semester_label' => $schoolClass->academicYear?->semester?->label(),
            ],
            'subjects' => $subjects,
            'teachers' => $teachers,
            'filters' => [
                'search' => $search,
            ],
            'stats' => [
                'total' => $totalSubjectTeachers,
                'active' => $activeCount,
                'inactive' => $totalSubjectTeachers - $activeCount,
            ],
        ]);
    }

    /**
     * Store a newly created subject teacher assignment.
     */
    public function store(StoreSubjectTeacherRequest $request, SchoolClass $schoolClass): RedirectResponse
    {
        $this->authorizeSubjectTeacherManage($request->user(), $schoolClass);

        try {
            SubjectTeacher::create([
                'school_class_id' => $schoolClass->id,
                'academic_year_id' => $schoolClass->academic_year_id,
                'subject_id' => $request->validated('subject_id'),
                'teacher_id' => $request->validated('teacher_id'),
                'is_active' => true,
            ]);
        } catch (UniqueConstraintViolationException) {
            return back()
                ->withErrors([
                    'subject_id' => 'Mata pelajaran ini sudah memiliki guru pengampuh di kelas tersebut.',
                ])
                ->withInput();
        }

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Berhasil Ditambahkan',
            'description' => 'Guru pengampuh mata pelajaran telah ditentukan.',
        ]);
    }

    /**
     * Update the specified subject teacher assignment.
     */
    public function update(UpdateSubjectTeacherRequest $request, SchoolClass $schoolClass, SubjectTeacher $subjectTeacher): RedirectResponse
    {
        $this->authorizeSubjectTeacherManage($request->user(), $subjectTeacher->schoolClass);

        $subjectTeacher->update([
            'teacher_id' => $request->validated('teacher_id'),
        ]);

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Berhasil Diperbarui',
            'description' => 'Guru pengampuh mata pelajaran telah diperbarui.',
        ]);
    }

    /**
     * Toggle the active status of the specified subject teacher assignment.
     */
    public function toggleStatus(Request $request, SchoolClass $schoolClass, SubjectTeacher $subjectTeacher): RedirectResponse
    {
        $this->authorizeSubjectTeacherManage($request->user(), $subjectTeacher->schoolClass);

        $wasActive = $subjectTeacher->is_active;

        $subjectTeacher->update([
            'is_active' => ! $wasActive,
        ]);

        $isActive = $subjectTeacher->fresh()->is_active;

        return back()->with('toast', [
            'type' => 'success',
            'title' => $isActive ? 'Penugasan Diaktifkan' : 'Penugasan Dinonaktifkan',
            'description' => 'Status guru pengampuh mata pelajaran berhasil diubah.',
        ]);
    }

    /**
     * Remove the specified subject teacher assignment.
     */
    public function destroy(Request $request, SchoolClass $schoolClass, SubjectTeacher $subjectTeacher): RedirectResponse
    {
        $this->authorizeSubjectTeacherManage($request->user(), $subjectTeacher->schoolClass);

        if ($subjectTeacher->is_active) {
            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Tidak Dapat Dihapus',
                'description' => 'Penugasan guru pengampuh masih aktif. Nonaktifkan terlebih dahulu sebelum menghapusnya.',
            ]);
        }

        $subjectTeacher->delete();

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Berhasil Dihapus',
            'description' => 'Penugasan guru pengampuh mata pelajaran telah dihapus.',
        ]);
    }

    /**
     * Determine whether the user may access the subject teacher module.
     */
    private function authorizeSubjectTeacherAccess(?User $user, SchoolClass $schoolClass): void
    {
        if ($user === null) {
            abort(403);
        }

        if ($user->isAdministrator() || $user->isSchoolAdmin()) {
            return;
        }

        if (
            $user->isHeadmaster()
            && in_array($schoolClass->school_id, $user->accessibleSchoolIds())
        ) {
            return;
        }

        abort(403);
    }

    /**
     * Determine whether the user may make changes to subject teacher assignments.
     * Kepala sekolah (headmaster) hanya boleh melihat.
     */
    private function authorizeSubjectTeacherManage(User $user, SchoolClass $schoolClass): void
    {
        if ($user->isAdministrator()) {
            return;
        }

        if (
            $user->isSchoolAdmin()
            && in_array($schoolClass->school_id, $user->accessibleSchoolIds())
        ) {
            return;
        }

        abort(403);
    }

    /**
     * Transform a subject teacher into its frontend representation.
     *
     * @return array<string, mixed>
     */
    private function presentSubjectTeacher(SubjectTeacher $subjectTeacher): array
    {
        return [
            'id' => $subjectTeacher->id,
            'subject_id' => $subjectTeacher->subject_id,
            'subject_code' => $subjectTeacher->subject?->code,
            'subject_name' => $subjectTeacher->subject?->name,
            'teacher_id' => $subjectTeacher->teacher_id,
            'teacher_name' => $subjectTeacher->teacher?->name,
            'teacher_email' => $subjectTeacher->teacher?->email,
            'academic_year_id' => $subjectTeacher->academic_year_id,
            'academic_year_code' => $subjectTeacher->academicYear?->code,
            'academic_year_name' => $subjectTeacher->academicYear?->name,
            'academic_year_semester_label' => $subjectTeacher->academicYear?->semester?->label(),
            'is_active' => (bool) $subjectTeacher->is_active,
        ];
    }
}
