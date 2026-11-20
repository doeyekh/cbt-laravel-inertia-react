<?php

namespace Tests\Feature\Admin;

use App\Enums\ExamPackageStatus;
use App\Enums\ExamScheduleStatus;
use App\Models\AcademicYear;
use App\Models\ClassMember;
use App\Models\ExamAttendance;
use App\Models\ExamPackage;
use App\Models\ExamParticipant;
use App\Models\ExamSchedule;
use App\Models\ExamType;
use App\Models\Room;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Session;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AttendanceControllerTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $academicYear;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->academicYear = AcademicYear::factory()->active()->create();
        $this->admin = User::factory()->administrator()->create();
    }

    public function test_index_requires_school_admin_access(): void
    {
        $user = User::factory()->guru()->create();

        $this->actingAs($user)
            ->get(route('admin.attendances.index'))
            ->assertForbidden();
    }

    public function test_index_lists_schedules_and_participants(): void
    {
        $context = $this->createScheduleContext();

        $this->actingAs($this->admin)
            ->get(route('admin.attendances.index', [
                'exam_schedule_id' => $context['schedule']->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Attendance/Index')
                ->where('selectedSchedule.id', $context['schedule']->id)
                ->has('participants', 2)
                ->where('participants.0.student_id', $context['student1']->id)
                ->where('participants.1.student_id', $context['student2']->id)
                ->where('attendanceCount', 2));
    }

    public function test_store_saves_attendance_records(): void
    {
        $context = $this->createScheduleContext();

        $this->actingAs($this->admin)
            ->post(route('admin.attendances.store'), [
                'exam_schedule_id' => $context['schedule']->id,
                'attendances' => [
                    [
                        'exam_participant_id' => $context['participant1']->id,
                        'status' => 'hadir',
                    ],
                    [
                        'exam_participant_id' => $context['participant2']->id,
                        'status' => 'tidak_hadir',
                    ],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('toast.type', 'success');

        $this->assertDatabaseHas('exam_attendances', [
            'exam_schedule_id' => $context['schedule']->id,
            'exam_participant_id' => $context['participant1']->id,
            'status' => 'hadir',
        ]);
        $this->assertDatabaseHas('exam_attendances', [
            'exam_schedule_id' => $context['schedule']->id,
            'exam_participant_id' => $context['participant2']->id,
            'status' => 'tidak_hadir',
        ]);
        $this->assertDatabaseMissing('exam_attendances', [
            'exam_schedule_id' => $context['schedule']->id,
            'exam_participant_id' => $context['participant2']->id,
            'status' => 'hadir',
        ]);

        $this->assertNotNull(
            ExamAttendance::query()
                ->where('exam_participant_id', $context['participant1']->id)
                ->value('check_in_time'),
        );
    }

    public function test_update_changes_single_attendance_status(): void
    {
        $context = $this->createScheduleContext();

        $attendance = ExamAttendance::factory()->create([
            'exam_schedule_id' => $context['schedule']->id,
            'exam_participant_id' => $context['participant1']->id,
            'status' => 'tidak_hadir',
        ]);

        $this->actingAs($this->admin)
            ->patch(route('admin.attendances.update', $attendance), [
                'status' => 'hadir',
            ])
            ->assertRedirect()
            ->assertSessionHas('toast.type', 'success');

        $this->assertDatabaseHas('exam_attendances', [
            'id' => $attendance->id,
            'status' => 'hadir',
        ]);
        $this->assertNotNull($attendance->fresh()->check_in_time);
    }

    public function test_update_sets_no_check_in_when_not_present(): void
    {
        $context = $this->createScheduleContext();

        $attendance = ExamAttendance::factory()->create([
            'exam_schedule_id' => $context['schedule']->id,
            'exam_participant_id' => $context['participant1']->id,
            'status' => 'hadir',
            'check_in_time' => now(),
        ]);

        $this->actingAs($this->admin)
            ->patch(route('admin.attendances.update', $attendance), [
                'status' => 'sakit',
            ])
            ->assertRedirect();

        $this->assertNull($attendance->fresh()->check_in_time);
    }

    public function test_store_ignores_participants_from_other_schedule(): void
    {
        $context = $this->createScheduleContext();

        $otherSchedule = ExamSchedule::factory()->forPackage($context['package'])->create([
            'class_id' => $context['schoolClass']->id,
            'subject_id' => Subject::factory()->create()->id,
            'session_id' => $context['session']->id,
            'room_id' => $context['room']->id,
            'exam_date' => $context['schedule']->exam_date,
            'open_time' => '08:00:00',
            'close_time' => '10:00:00',
            'status' => 'aktif',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.attendances.store'), [
                'exam_schedule_id' => $otherSchedule->id,
                'attendances' => [
                    [
                        'exam_participant_id' => $context['participant1']->id,
                        'status' => 'hadir',
                    ],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('exam_attendances', [
            'exam_schedule_id' => $otherSchedule->id,
            'exam_participant_id' => $context['participant1']->id,
            'status' => 'hadir',
        ]);
    }

    /**
     * @return array{
     *     schedule: ExamSchedule,
     *     package: ExamPackage,
     *     subject: Subject,
     *     schoolClass: SchoolClass,
     *     session: Session,
     *     room: Room,
     *     student1: Student,
     *     student2: Student,
     *     participant1: ExamParticipant,
     *     participant2: ExamParticipant
     * }
     */
    private function createScheduleContext(): array
    {
        $schoolClass = SchoolClass::factory()
            ->forSchool($this->school, $this->academicYear->id)
            ->active()
            ->create();
        $subject = Subject::factory()->create();
        $room = Room::factory()->forSchool($this->school)->create();
        $session = Session::factory()->forSchool($this->school)->create();

        $student1 = Student::factory()->create(['name' => 'Siswa Satu']);
        $student2 = Student::factory()->create(['name' => 'Siswa Dua']);

        ClassMember::factory()->forClass($schoolClass, $student1)->create();
        ClassMember::factory()->forClass($schoolClass, $student2)->create();

        $participant1 = ExamParticipant::factory()
            ->forSchoolYear($this->school, $this->academicYear, $student1)
            ->placedIn($room->id, $session->id)
            ->create();
        $participant2 = ExamParticipant::factory()
            ->forSchoolYear($this->school, $this->academicYear, $student2)
            ->placedIn($room->id, $session->id)
            ->create();

        $examType = ExamType::factory()->active()->create([
            'school_id' => $this->school->id,
        ]);
        $today = now()->toDateString();
        $package = ExamPackage::factory()
            ->forSchool($this->school, $this->academicYear, $examType)
            ->withStatus(ExamPackageStatus::Aktif)
            ->create([
                'start_date' => $today,
                'end_date' => $today,
            ]);
        $schedule = ExamSchedule::factory()
            ->forPackage($package)
            ->create([
                'class_id' => $schoolClass->id,
                'subject_id' => $subject->id,
                'session_id' => $session->id,
                'room_id' => $room->id,
                'exam_date' => $today,
                'open_time' => '07:00:00',
                'close_time' => '10:00:00',
                'status' => ExamScheduleStatus::Aktif,
            ]);

        return [
            'schedule' => $schedule,
            'package' => $package,
            'subject' => $subject,
            'schoolClass' => $schoolClass,
            'session' => $session,
            'room' => $room,
            'student1' => $student1,
            'student2' => $student2,
            'participant1' => $participant1,
            'participant2' => $participant2,
        ];
    }
}
