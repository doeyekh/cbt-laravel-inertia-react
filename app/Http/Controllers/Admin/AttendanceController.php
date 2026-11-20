<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ExamAttendanceStatus;
use App\Http\Controllers\Admin\Concerns\AuthorizesExamParticipantAccess;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ClassMember;
use App\Models\ExamAttendance;
use App\Models\ExamParticipant;
use App\Models\ExamSchedule;
use App\Models\Room;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Session;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    use AuthorizesExamParticipantAccess;

    /**
     * Display the attendance list for an exam schedule.
     */
    public function index(Request $request): Response
    {
        $this->authorizeExamParticipantAccess($request->user());

        $schoolIds = $request->user()->accessibleSchoolIds();

        $request->validate([
            'school_id' => ['nullable', 'integer'],
            'academic_year_id' => ['nullable', 'integer'],
            'class_id' => ['nullable', 'integer'],
            'session_id' => ['nullable', 'integer'],
            'room_id' => ['nullable', 'integer'],
            'exam_schedule_id' => ['nullable', 'integer', 'exists:exam_schedules,id'],
            'status' => ['nullable', 'string'],
        ]);

        $schoolId = (int) $request->integer('school_id');
        $academicYearId = (int) $request->integer('academic_year_id');
        $classId = (int) $request->integer('class_id');
        $sessionId = (int) $request->integer('session_id');
        $roomId = (int) $request->integer('room_id');
        $scheduleId = (int) $request->integer('exam_schedule_id');
        $statusFilter = $request->string('status')->toString();

        if ($schoolId > 0 && ! in_array($schoolId, $schoolIds, true)) {
            $schoolId = 0;
        }

        if ($academicYearId <= 0) {
            $academicYearId = (int) (AcademicYear::active()?->id ?? 0);
        }

        $schedulesQuery = ExamSchedule::query()
            ->with(['examPackage', 'subject', 'schoolClass', 'session', 'room'])
            ->whereHas('examPackage', fn ($query) => $query->whereIn('school_id', $schoolIds))
            ->when($academicYearId > 0, fn ($query) => $query
                ->whereHas('examPackage', fn ($packageQuery) => $packageQuery
                    ->where('academic_year_id', $academicYearId)))
            ->when($classId > 0, fn ($query) => $query->where('class_id', $classId))
            ->when($sessionId > 0, fn ($query) => $query->where('session_id', $sessionId))
            ->when($roomId > 0, fn ($query) => $query->where('room_id', $roomId))
            ->orderBy('exam_date')
            ->orderBy('open_time');

        $schedules = $schedulesQuery->get();

        $participants = collect();
        $selectedSchedule = null;

        if ($scheduleId > 0) {
            $selectedSchedule = $schedules->firstWhere('id', $scheduleId);

            abort_unless($selectedSchedule !== null, 404);

            $this->authorizeParticipantSchoolAccess(
                $request->user(),
                (int) $selectedSchedule->examPackage?->school_id,
            );

            $classMembers = ClassMember::query()
                ->where('class_id', $selectedSchedule->class_id)
                ->where('academic_year_id', $selectedSchedule->examPackage->academic_year_id)
                ->with(['student:id,name,nisn', 'schoolClass:id,name'])
                ->orderBy('student_id')
                ->get();

            $existingAttendances = ExamAttendance::query()
                ->where('exam_schedule_id', $scheduleId)
                ->get()
                ->keyBy('exam_participant_id');

            $participants = $classMembers->map(function (ClassMember $classMember) use (
                $selectedSchedule,
                $existingAttendances,
            ): array {
                $student = $classMember->student;
                $participant = ExamParticipant::query()
                    ->where('school_id', $selectedSchedule->examPackage->school_id)
                    ->where('academic_year_id', $selectedSchedule->examPackage->academic_year_id)
                    ->where('student_id', $student->id)
                    ->first();

                $attendance = $existingAttendances->get($participant?->id);

                return [
                    'participant_id' => $participant?->id,
                    'student_id' => $student->id,
                    'name' => $student->name,
                    'nisn' => $student->nisn,
                    'participant_number' => $participant?->participant_number ?? '-',
                    'class_name' => $classMember->schoolClass?->name,
                    'status' => $attendance?->status?->value ?? ExamAttendanceStatus::TidakHadir->value,
                    'check_in_time' => $attendance?->check_in_time?->toIso8601String(),
                    'has_credential' => $participant?->hasCredential() ?? false,
                ];
            });
        }

        $statuses = collect(ExamAttendanceStatus::cases())->map(fn (ExamAttendanceStatus $status): array => [
            'value' => $status->value,
            'label' => $status->label(),
        ])->values()->all();

        return Inertia::render('Admin/Attendance/Index', [
            'title' => 'Daftar Hadir Ujian',
            'schedules' => $schedules->map(fn (ExamSchedule $schedule): array => [
                'id' => $schedule->id,
                'subject' => $schedule->subject?->name,
                'class' => $schedule->schoolClass?->name,
                'session' => $schedule->session?->name,
                'room' => $schedule->room?->name,
                'exam_date' => $schedule->exam_date?->format('Y-m-d'),
                'open_time' => $schedule->open_time,
                'close_time' => $schedule->close_time,
                'package' => $schedule->examPackage?->name,
                'school' => $schedule->examPackage?->school?->name,
            ]),
            'selectedSchedule' => $selectedSchedule ? [
                'id' => $selectedSchedule->id,
                'subject' => $selectedSchedule->subject?->name,
                'class' => $selectedSchedule->schoolClass?->name,
                'session' => $selectedSchedule->session?->name,
                'room' => $selectedSchedule->room?->name,
                'exam_date' => $selectedSchedule->exam_date?->format('Y-m-d'),
                'open_time' => $selectedSchedule->open_time,
                'close_time' => $selectedSchedule->close_time,
                'package' => $selectedSchedule->examPackage?->name,
                'school' => $selectedSchedule->examPackage?->school?->name,
            ] : null,
            'participants' => $participants->values()->all(),
            'attendanceCount' => $participants->count(),
            'presentCount' => $participants->where('status', 'hadir')->count(),
            'statuses' => $statuses,
            'filters' => [
                'school_id' => $schoolId > 0 ? $schoolId : null,
                'academic_year_id' => $academicYearId > 0 ? $academicYearId : null,
                'class_id' => $classId > 0 ? $classId : null,
                'session_id' => $sessionId > 0 ? $sessionId : null,
                'room_id' => $roomId > 0 ? $roomId : null,
                'exam_schedule_id' => $scheduleId > 0 ? $scheduleId : null,
                'status' => $statusFilter !== '' ? $statusFilter : null,
            ],
            'schools' => School::query()
                ->whereIn('id', $schoolIds)
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get(['id', 'name']),
            'classes' => SchoolClass::query()
                ->whereIn('school_id', $schoolIds)
                ->when($academicYearId > 0, fn ($query) => $query->where('academic_year_id', $academicYearId))
                ->orderBy('name')
                ->get(['id', 'school_id', 'academic_year_id', 'code', 'name']),
            'sessions' => Session::query()
                ->whereIn('school_id', $schoolIds)
                ->orderBy('start_time')
                ->get(['id', 'school_id', 'code', 'name', 'start_time', 'end_time', 'is_active']),
            'rooms' => Room::query()
                ->whereIn('school_id', $schoolIds)
                ->orderBy('name')
                ->get(['id', 'school_id', 'code', 'name', 'capacity', 'is_active']),
            'activeAcademicYear' => AcademicYear::active()?->only(['id', 'code', 'name']),
        ]);
    }

    /**
     * Bulk save attendance records for all participants of a schedule.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorizeExamParticipantAccess($request->user());

        $request->validate([
            'exam_schedule_id' => ['required', 'integer', 'exists:exam_schedules,id'],
            'attendances' => ['required', 'array'],
            'attendances.*' => ['array'],
            'attendances.*.exam_participant_id' => ['required', 'integer', 'exists:exam_participants,id'],
            'attendances.*.status' => ['required', 'string', 'in:'.implode(',', array_column(ExamAttendanceStatus::cases(), 'value'))],
        ]);

        $schedule = ExamSchedule::with('examPackage')->findOrFail($request->integer('exam_schedule_id'));

        $this->authorizeParticipantSchoolAccess(
            $request->user(),
            (int) $schedule->examPackage?->school_id,
        );

        $saved = 0;

        foreach ($request->array('attendances') as $record) {
            $participantId = (int) $record['exam_participant_id'];
            $status = $record['status'];

            $existing = ExamAttendance::query()
                ->where('exam_schedule_id', $schedule->id)
                ->where('exam_participant_id', $participantId)
                ->first();

            $checkInTime = $status === ExamAttendanceStatus::Hadir->value
                ? now()
                : null;

            if ($existing) {
                $existing->update([
                    'status' => $status,
                    'check_in_time' => $checkInTime,
                ]);
            } else {
                ExamAttendance::query()->create([
                    'exam_schedule_id' => $schedule->id,
                    'exam_participant_id' => $participantId,
                    'status' => $status,
                    'check_in_time' => $checkInTime,
                ]);
            }

            $saved++;
        }

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Kehadiran Disimpan',
            'description' => "{$saved} catatan kehadiran telah disimpan.",
        ]);
    }

    /**
     * Update the attendance status of a single participant.
     */
    public function update(Request $request, ExamAttendance $attendance): RedirectResponse
    {
        $this->authorizeExamParticipantAccess($request->user());

        $request->validate([
            'status' => ['required', 'string', 'in:'.implode(',', array_column(ExamAttendanceStatus::cases(), 'value'))],
        ]);

        $attendance->load('participant');

        $this->authorizeParticipantAccess($request->user(), $attendance->participant);

        $status = (string) $request->string('status');

        $attendance->update([
            'status' => $status,
            'check_in_time' => $status === ExamAttendanceStatus::Hadir->value
                ? ($attendance->check_in_time ?? now())
                : null,
        ]);

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Kehadiran Diperbarui',
            'description' => "Status kehadiran telah diperbarui menjadi {$attendance->status->label()}.",
        ]);
    }
}
