<?php

namespace Tests\Feature\Admin;

use App\Enums\ExamPackageStatus;
use App\Enums\ExamScheduleStatus;
use App\Models\AcademicYear;
use App\Models\ExamPackage;
use App\Models\ExamSchedule;
use App\Models\ExamType;
use App\Models\QuestionBank;
use App\Models\Room;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Session;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExamScheduleControllerTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $academicYear;

    private ExamPackage $examPackage;

    private SchoolClass $schoolClass;

    private Subject $subject;

    private Session $session;

    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->actingAs(User::factory()->administrator()->create());

        $this->academicYear = AcademicYear::factory()->create();
        $examType = ExamType::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);

        $this->examPackage = ExamPackage::factory()->create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'exam_type_id' => $examType->id,
            'start_date' => '2026-09-14',
            'end_date' => '2026-09-19',
            'status' => ExamPackageStatus::Draft,
        ]);

        $this->schoolClass = SchoolClass::factory()->create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
        ]);
        $this->subject = Subject::factory()->create();
        $this->bankFor($this->subject, $this->examPackage, $this->schoolClass);
        $this->session = Session::factory()->create([
            'school_id' => $this->school->id,
            'start_time' => '07:00:00',
            'end_time' => '09:00:00',
        ]);
        $this->room = Room::factory()->create(['school_id' => $this->school->id]);
    }

    public function test_store_creates_an_exam_schedule(): void
    {
        $this->post($this->scheduleStoreUrl(), $this->schedulePayload())
            ->assertRedirect()
            ->assertSessionHas('toast.type', 'success');

        $this->assertDatabaseHas('exam_schedules', [
            'exam_package_id' => $this->examPackage->id,
            'class_id' => $this->schoolClass->id,
            'subject_id' => $this->subject->id,
            'session_id' => $this->session->id,
            'room_id' => $this->room->id,
            'exam_date' => '2026-09-15 00:00:00',
            'duration_minutes' => 90,
            'open_time' => '07:00:00',
            'close_time' => '08:30:00',
            'status' => 'aktif',
        ]);
    }

    public function test_store_rejects_class_from_another_school(): void
    {
        $otherClass = SchoolClass::factory()->create([
            'school_id' => School::factory()->create()->id,
            'academic_year_id' => $this->academicYear->id,
        ]);

        $this->post($this->scheduleStoreUrl(), $this->schedulePayload([
            'class_id' => $otherClass->id,
        ]))->assertSessionHasErrors('class_id');

        $this->assertDatabaseCount('exam_schedules', 0);
    }

    public function test_store_rejects_session_from_another_school(): void
    {
        $otherSession = Session::factory()->create([
            'school_id' => School::factory()->create()->id,
        ]);

        $this->post($this->scheduleStoreUrl(), $this->schedulePayload([
            'session_id' => $otherSession->id,
        ]))->assertSessionHasErrors('session_id');
    }

    public function test_store_rejects_room_from_another_school(): void
    {
        $otherRoom = Room::factory()->create([
            'school_id' => School::factory()->create()->id,
        ]);

        $this->post($this->scheduleStoreUrl(), $this->schedulePayload([
            'room_id' => $otherRoom->id,
        ]))->assertSessionHasErrors('room_id');
    }

    public function test_store_rejects_date_outside_package_period(): void
    {
        $this->post($this->scheduleStoreUrl(), $this->schedulePayload([
            'exam_date' => '2026-09-20',
        ]))->assertSessionHasErrors('exam_date');
    }

    public function test_store_rejects_duplicate_class_and_subject_in_the_same_package(): void
    {
        ExamSchedule::factory()->forPackage($this->examPackage)->create(
            $this->schedulePayload()
        );

        $this->post($this->scheduleStoreUrl(), $this->schedulePayload([
            'room_id' => Room::factory()->create(['school_id' => $this->school->id])->id,
        ]))->assertSessionHasErrors('subject_id');
    }

    public function test_store_rejects_room_conflict_on_the_same_date_and_session(): void
    {
        ExamSchedule::factory()->forPackage($this->examPackage)->create(
            $this->schedulePayload()
        );

        $otherClass = SchoolClass::factory()->create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
        ]);
        $otherSubject = Subject::factory()->create();
        $this->bankFor($otherSubject, $this->examPackage, $otherClass);

        $this->post($this->scheduleStoreUrl(), $this->schedulePayload([
            'class_id' => $otherClass->id,
            'subject_id' => $otherSubject->id,
        ]))->assertSessionHasErrors('room_id');
    }

    public function test_store_rejects_room_conflict_with_another_package_in_the_same_school(): void
    {
        $otherPackage = ExamPackage::factory()->create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'exam_type_id' => $this->examPackage->exam_type_id,
            'start_date' => '2026-09-14',
            'end_date' => '2026-09-19',
        ]);
        $otherClass = SchoolClass::factory()->create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
        ]);

        ExamSchedule::factory()->forPackage($otherPackage)->create(
            $this->schedulePayload(['class_id' => $otherClass->id])
        );

        $otherSubject = Subject::factory()->create();
        $this->bankFor($otherSubject, $this->examPackage, $this->schoolClass);

        $this->post($this->scheduleStoreUrl(), $this->schedulePayload([
            'subject_id' => $otherSubject->id,
        ]))->assertSessionHasErrors('room_id');
    }

    public function test_store_rejects_class_conflict_on_the_same_date_and_session(): void
    {
        ExamSchedule::factory()->forPackage($this->examPackage)->create(
            $this->schedulePayload()
        );

        $otherSubject = Subject::factory()->create();
        $this->bankFor($otherSubject, $this->examPackage, $this->schoolClass);

        $this->post($this->scheduleStoreUrl(), $this->schedulePayload([
            'subject_id' => $otherSubject->id,
            'room_id' => Room::factory()->create(['school_id' => $this->school->id])->id,
        ]))->assertSessionHasErrors('class_id');
    }

    public function test_store_rejects_schedule_for_completed_package(): void
    {
        $this->examPackage->update(['status' => ExamPackageStatus::Selesai]);

        $this->post($this->scheduleStoreUrl(), $this->schedulePayload())
            ->assertSessionHasErrors('exam_package');
    }

    public function test_update_changes_an_exam_schedule(): void
    {
        $schedule = ExamSchedule::factory()->forPackage($this->examPackage)->create(
            $this->schedulePayload()
        );
        $newSubject = Subject::factory()->create();
        $this->bankFor($newSubject, $this->examPackage, $this->schoolClass);
        $newRoom = Room::factory()->create(['school_id' => $this->school->id]);

        $this->put($this->scheduleUpdateUrl($schedule), $this->schedulePayload([
            'subject_id' => $newSubject->id,
            'room_id' => $newRoom->id,
            'exam_date' => '2026-09-16',
        ]))->assertRedirect()->assertSessionHas('toast.type', 'success');

        $schedule->refresh();

        $this->assertSame($newSubject->id, $schedule->subject_id);
        $this->assertSame($newRoom->id, $schedule->room_id);
        $this->assertSame('2026-09-16', $schedule->exam_date->toDateString());
    }

    public function test_update_does_not_conflict_with_itself(): void
    {
        $schedule = ExamSchedule::factory()->forPackage($this->examPackage)->create(
            $this->schedulePayload()
        );

        $this->put($this->scheduleUpdateUrl($schedule), $this->schedulePayload())
            ->assertRedirect()
            ->assertSessionHasNoErrors();
    }

    public function test_destroy_deletes_an_exam_schedule(): void
    {
        $schedule = ExamSchedule::factory()->forPackage($this->examPackage)->create(
            $this->schedulePayload()
        );

        $this->delete($this->scheduleUpdateUrl($schedule))
            ->assertRedirect()
            ->assertSessionHas('toast.type', 'success');

        $this->assertDatabaseMissing('exam_schedules', ['id' => $schedule->id]);
    }

    public function test_schedule_must_belong_to_the_package_in_the_route(): void
    {
        $otherPackage = ExamPackage::factory()->create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'exam_type_id' => $this->examPackage->exam_type_id,
            'start_date' => '2026-09-14',
            'end_date' => '2026-09-19',
        ]);
        $schedule = ExamSchedule::factory()->forPackage($otherPackage)->create(
            $this->schedulePayload()
        );

        $this->delete($this->scheduleUpdateUrl($schedule))->assertNotFound();
    }

    public function test_school_admin_can_manage_schedules_in_their_school_only(): void
    {
        $schoolAdmin = User::factory()->guru()->create();
        $this->school->update(['admin_id' => $schoolAdmin->id]);

        $this->actingAs($schoolAdmin)
            ->post($this->scheduleStoreUrl(), $this->schedulePayload())
            ->assertRedirect();

        $otherSchool = School::factory()->create();
        $otherYear = AcademicYear::factory()->create();
        $otherType = ExamType::factory()->create(['school_id' => $otherSchool->id, 'is_active' => true]);
        $otherPackage = ExamPackage::factory()->create([
            'school_id' => $otherSchool->id,
            'academic_year_id' => $otherYear->id,
            'exam_type_id' => $otherType->id,
            'start_date' => '2026-09-14',
            'end_date' => '2026-09-19',
        ]);
        $otherClass = SchoolClass::factory()->create([
            'school_id' => $otherSchool->id,
            'academic_year_id' => $otherYear->id,
        ]);
        $otherSession = Session::factory()->create(['school_id' => $otherSchool->id]);
        $otherRoom = Room::factory()->create(['school_id' => $otherSchool->id]);
        $otherSubject = Subject::factory()->create();
        $this->bankFor($otherSubject, $otherPackage, $otherClass);

        $this->actingAs($schoolAdmin)
            ->post(route('admin.exam-schedules.store', $otherPackage), $this->schedulePayload([
                'class_id' => $otherClass->id,
                'subject_id' => $otherSubject->id,
                'session_id' => $otherSession->id,
                'room_id' => $otherRoom->id,
            ]))
            ->assertForbidden();
    }

    public function test_headmaster_cannot_manage_schedules(): void
    {
        $headmaster = User::factory()->guru()->create();
        $this->school->update(['headmaster_id' => $headmaster->id]);

        $this->actingAs($headmaster)
            ->post($this->scheduleStoreUrl(), $this->schedulePayload())
            ->assertForbidden();
    }

    public function test_regular_guru_cannot_manage_schedules(): void
    {
        $this->actingAs(User::factory()->guru()->create())
            ->post($this->scheduleStoreUrl(), $this->schedulePayload())
            ->assertForbidden();
    }

    public function test_show_only_offers_subjects_backed_by_a_matching_question_bank(): void
    {
        $otherClass = SchoolClass::factory()->create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
        ]);
        $otherSubject = Subject::factory()->create();
        $this->bankFor($otherSubject, $this->examPackage, $otherClass);

        $this->get(route('admin.exam-packages.show', $this->examPackage))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ExamPackages/Show')
                ->has("subjectsByClass.{$this->schoolClass->id}", 1)
                ->where("subjectsByClass.{$this->schoolClass->id}.0.id", $this->subject->id)
                ->has("subjectsByClass.{$otherClass->id}", 1)
                ->where("subjectsByClass.{$otherClass->id}.0.id", $otherSubject->id)
            );
    }

    public function test_store_rejects_subject_without_a_matching_question_bank(): void
    {
        $this->post($this->scheduleStoreUrl(), $this->schedulePayload([
            'subject_id' => Subject::factory()->create()->id,
        ]))->assertSessionHasErrors('subject_id');

        $this->assertDatabaseCount('exam_schedules', 0);
    }

    public function test_store_rejects_a_class_the_question_bank_is_not_assigned_to(): void
    {
        $otherClass = SchoolClass::factory()->create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
        ]);

        $this->post($this->scheduleStoreUrl(), $this->schedulePayload([
            'class_id' => $otherClass->id,
        ]))->assertSessionHasErrors('class_id');

        $this->assertDatabaseCount('exam_schedules', 0);
    }

    public function test_store_rejects_close_time_that_is_not_after_open_time(): void
    {
        $this->post($this->scheduleStoreUrl(), $this->schedulePayload([
            'open_time' => '09:00:00',
            'close_time' => '09:00:00',
        ]))->assertSessionHasErrors('close_time');

        $this->assertDatabaseCount('exam_schedules', 0);
    }

    public function test_store_ignores_a_submitted_status(): void
    {
        $this->post($this->scheduleStoreUrl(), $this->schedulePayload([
            'status' => 'nonaktif',
        ]))->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('exam_schedules', ['status' => 'aktif']);
    }

    public function test_store_normalizes_times_without_seconds(): void
    {
        $this->post($this->scheduleStoreUrl(), $this->schedulePayload([
            'open_time' => '07:00',
            'close_time' => '08:30',
        ]))->assertRedirect();

        $this->assertDatabaseHas('exam_schedules', [
            'open_time' => '07:00:00',
            'close_time' => '08:30:00',
        ]);
    }

    public function test_store_persists_the_schedule_settings(): void
    {
        $this->post($this->scheduleStoreUrl(), $this->schedulePayload([
            'shuffle_questions' => false,
            'shuffle_options' => false,
            'show_score_to_student' => false,
        ]))->assertRedirect();

        $schedule = ExamSchedule::query()->firstOrFail();

        $this->assertFalse($schedule->shuffle_questions);
        $this->assertFalse($schedule->shuffle_options);
        $this->assertFalse($schedule->show_score_to_student);
        $this->assertSame(ExamScheduleStatus::Aktif, $schedule->status);
    }

    public function test_update_changes_the_schedule_settings(): void
    {
        $schedule = ExamSchedule::factory()->forPackage($this->examPackage)->create(
            $this->schedulePayload()
        );

        $this->put($this->scheduleUpdateUrl($schedule), $this->schedulePayload([
            'open_time' => '10:00:00',
            'close_time' => '11:30:00',
            'shuffle_questions' => false,
            'shuffle_options' => false,
            'show_score_to_student' => false,
        ]))->assertRedirect()->assertSessionHasNoErrors();

        $schedule->refresh();

        $this->assertSame('10:00:00', $schedule->open_time);
        $this->assertSame('11:30:00', $schedule->close_time);
        $this->assertFalse($schedule->shuffle_questions);
        $this->assertFalse($schedule->shuffle_options);
        $this->assertFalse($schedule->show_score_to_student);
    }

    public function test_toggle_status_switches_the_schedule_status(): void
    {
        $schedule = ExamSchedule::factory()->forPackage($this->examPackage)->create(
            $this->schedulePayload()
        );

        $this->patch($this->scheduleStatusUrl($schedule))
            ->assertRedirect()
            ->assertSessionHas('toast.type', 'success');

        $this->assertSame(ExamScheduleStatus::Nonaktif, $schedule->refresh()->status);

        $this->patch($this->scheduleStatusUrl($schedule))
            ->assertRedirect()
            ->assertSessionHas('toast.type', 'success');

        $this->assertSame(ExamScheduleStatus::Aktif, $schedule->refresh()->status);
    }

    public function test_toggle_status_is_blocked_for_a_completed_package(): void
    {
        $schedule = ExamSchedule::factory()->forPackage($this->examPackage)->create(
            $this->schedulePayload()
        );
        $this->examPackage->update(['status' => ExamPackageStatus::Selesai]);

        $this->patch($this->scheduleStatusUrl($schedule))
            ->assertRedirect()
            ->assertSessionHas('toast.type', 'error');

        $this->assertSame(ExamScheduleStatus::Aktif, $schedule->refresh()->status);
    }

    public function test_regular_guru_cannot_toggle_a_schedule_status(): void
    {
        $schedule = ExamSchedule::factory()->forPackage($this->examPackage)->create(
            $this->schedulePayload()
        );

        $this->actingAs(User::factory()->guru()->create())
            ->patch($this->scheduleStatusUrl($schedule))
            ->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function schedulePayload(array $overrides = []): array
    {
        return array_merge([
            'class_id' => $this->schoolClass->id,
            'subject_id' => $this->subject->id,
            'session_id' => $this->session->id,
            'room_id' => $this->room->id,
            'exam_date' => '2026-09-15',
            'duration_minutes' => 90,
            'open_time' => '07:00:00',
            'close_time' => '08:30:00',
            'shuffle_questions' => true,
            'shuffle_options' => true,
            'show_score_to_student' => true,
        ], $overrides);
    }

    /**
     * Create a question bank that matches the given exam package and is assigned to the given class.
     */
    private function bankFor(Subject $subject, ExamPackage $examPackage, SchoolClass $schoolClass): QuestionBank
    {
        $questionBank = QuestionBank::factory()->create([
            'school_id' => $examPackage->school_id,
            'academic_year_id' => $examPackage->academic_year_id,
            'exam_type_id' => $examPackage->exam_type_id,
            'subject_id' => $subject->id,
        ]);

        $questionBank->classes()->attach($schoolClass->id);

        return $questionBank;
    }

    private function scheduleStoreUrl(): string
    {
        return route('admin.exam-schedules.store', $this->examPackage);
    }

    private function scheduleUpdateUrl(ExamSchedule $examSchedule): string
    {
        return route('admin.exam-schedules.update', [
            'examPackage' => $this->examPackage->id,
            'examSchedule' => $examSchedule->id,
        ]);
    }

    private function scheduleStatusUrl(ExamSchedule $examSchedule): string
    {
        return route('admin.exam-schedules.toggle-status', [
            'examPackage' => $this->examPackage->id,
            'examSchedule' => $examSchedule->id,
        ]);
    }
}
