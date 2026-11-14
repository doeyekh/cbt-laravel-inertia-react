<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicYear;
use App\Models\ClassMember;
use App\Models\ExamParticipant;
use App\Models\Room;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Session;
use App\Models\Student;
use App\Models\StudentRegistration;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExamParticipantControllerTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $academicYear;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->academicYear = AcademicYear::factory()->create();
        $this->actingAs(User::factory()->administrator()->create());
    }

    public function test_index_lists_the_participants_of_every_accessible_school(): void
    {
        $class = $this->addClass($this->school, $this->academicYear);
        $student = $this->addStudent($this->school, $this->academicYear, $class, '20260001');
        $participant = $this->makeParticipant($student, $this->school, $this->academicYear);

        $this->get(route('admin.exam-participants.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ExamParticipants/Index')
                ->has('participants.data', 1)
                ->where('participants.data.0.id', $participant->id)
                ->where('participants.data.0.student_name', $student->name)
                ->where('stats.total', 1));
    }

    public function test_index_filters_by_school_and_academic_year(): void
    {
        $class = $this->addClass($this->school, $this->academicYear);
        $mine = $this->makeParticipant(
            $this->addStudent($this->school, $this->academicYear, $class, '20260001'),
            $this->school,
            $this->academicYear
        );

        $otherYear = AcademicYear::factory()->create();
        $this->makeParticipant(
            $this->addStudent($this->school, $otherYear, $this->addClass($this->school, $otherYear), '20260002'),
            $this->school,
            $otherYear
        );

        $otherSchool = School::factory()->create();
        $this->makeParticipant(
            $this->addStudent($otherSchool, $this->academicYear, $this->addClass($otherSchool, $this->academicYear), '20260003'),
            $otherSchool,
            $this->academicYear
        );

        $this->get(route('admin.exam-participants.index', ['school_id' => $this->school->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('participants.data', 2)
                ->where('stats.total', 2));

        $this->get(route('admin.exam-participants.index', [
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
        ]))->assertInertia(fn (Assert $page) => $page
            ->has('participants.data', 1)
            ->where('participants.data.0.id', $mine->id)
            ->where('stats.total', 1));
    }

    public function test_index_filters_by_class_session_room_and_status(): void
    {
        $venue = $this->prepareVenue($this->school);
        $classA = $this->addClass($this->school, $this->academicYear);
        $classB = $this->addClass($this->school, $this->academicYear);

        $placed = $this->makeParticipant(
            $this->addStudent($this->school, $this->academicYear, $classA, '20260001'),
            $this->school,
            $this->academicYear,
            ['room_id' => $venue['room']->id, 'session_id' => $venue['session']->id]
        );

        $this->makeParticipant(
            $this->addStudent($this->school, $this->academicYear, $classB, '20260002'),
            $this->school,
            $this->academicYear,
            ['username' => null, 'participant_number' => null, 'password' => null, 'password_plain' => null]
        );

        $this->get(route('admin.exam-participants.index', ['class_id' => $classA->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('participants.data', 1)
                ->where('participants.data.0.id', $placed->id));

        $this->get(route('admin.exam-participants.index', ['room_id' => $venue['room']->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('participants.data', 1)
                ->where('participants.data.0.id', $placed->id));

        $this->get(route('admin.exam-participants.index', ['session_id' => $venue['session']->id]))
            ->assertInertia(fn (Assert $page) => $page->has('participants.data', 1));

        $this->get(route('admin.exam-participants.index', ['status' => 'belum']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('participants.data', 1)
                ->where('stats.total', 1)
                ->where('stats.with_credential', 0));

        $this->get(route('admin.exam-participants.index', ['status' => 'lengkap']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('participants.data', 1)
                ->where('stats.total', 1)
                ->where('stats.with_credential', 1));
    }

    public function test_index_reports_stats_matching_the_filters(): void
    {
        $class = $this->addClass($this->school, $this->academicYear);

        $this->makeParticipant(
            $this->addStudent($this->school, $this->academicYear, $class, '20260001'),
            $this->school,
            $this->academicYear
        );

        $this->makeParticipant(
            $this->addStudent($this->school, $this->academicYear, $class, '20260002'),
            $this->school,
            $this->academicYear,
            ['username' => null, 'participant_number' => null, 'password' => null, 'password_plain' => null]
        );

        $this->get(route('admin.exam-participants.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.total', 2)
                ->where('stats.with_credential', 1)
                ->where('stats.without_placement', 2));
    }

    public function test_index_defaults_to_the_active_academic_year(): void
    {
        $activeYear = AcademicYear::factory()->active()->create();
        $inactiveYear = AcademicYear::factory()->inactive()->create();

        $this->makeParticipant(
            $this->addStudent($this->school, $activeYear, $this->addClass($this->school, $activeYear), '20260001'),
            $this->school,
            $activeYear
        );

        $this->makeParticipant(
            $this->addStudent($this->school, $inactiveYear, $this->addClass($this->school, $inactiveYear), '20260002'),
            $this->school,
            $inactiveYear
        );

        $this->get(route('admin.exam-participants.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('participants.data', 1)
                ->where('participants.data.0.academic_year_id', $activeYear->id)
                ->where('filters.academic_year_id', $activeYear->id)
                ->where('stats.total', 1)
                ->where('activeAcademicYear.id', $activeYear->id)
                ->missing('academicYears'));
    }

    public function test_sync_pulls_the_members_of_the_selected_classes(): void
    {
        $this->prepareVenue($this->school);

        $class = $this->addClass($this->school, $this->academicYear);
        $this->addStudent($this->school, $this->academicYear, $class, '20260001');
        $this->addStudent($this->school, $this->academicYear, $class, '20260002');

        $this->post(route('admin.exam-participants.sync'), [
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'class_ids' => [$class->id],
        ])->assertRedirect()->assertSessionHas('toast.type', 'success');

        $this->assertSame(
            2,
            ExamParticipant::query()
                ->where('school_id', $this->school->id)
                ->where('academic_year_id', $this->academicYear->id)
                ->count()
        );

        $participant = ExamParticipant::query()->firstOrFail();

        $this->assertNotNull($participant->room_id);
        $this->assertNotNull($participant->session_id);
    }

    public function test_sync_rejects_a_class_from_another_school(): void
    {
        $otherSchool = School::factory()->create();
        $otherClass = $this->addClass($otherSchool, $this->academicYear);
        $this->addStudent($otherSchool, $this->academicYear, $otherClass, '20260001');

        $this->post(route('admin.exam-participants.sync'), [
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'class_ids' => [$otherClass->id],
        ])->assertSessionHasErrors('class_ids');

        $this->assertSame(0, ExamParticipant::query()->count());
    }

    public function test_sync_does_not_duplicate_a_student_already_registered_in_the_same_school_and_year(): void
    {
        $this->prepareVenue($this->school);

        $class = $this->addClass($this->school, $this->academicYear);
        $student = $this->addStudent($this->school, $this->academicYear, $class, '20260001');

        $this->makeParticipant($student, $this->school, $this->academicYear);

        $this->post(route('admin.exam-participants.sync'), [
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'class_ids' => [$class->id],
        ])->assertSessionHas('toast.type', 'info');

        $this->assertSame(
            1,
            ExamParticipant::query()
                ->where('school_id', $this->school->id)
                ->where('academic_year_id', $this->academicYear->id)
                ->count()
        );
    }

    public function test_sync_requires_at_least_one_class(): void
    {
        $this->post(route('admin.exam-participants.sync'), [
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'class_ids' => [],
        ])->assertSessionHasErrors('class_ids');
    }

    public function test_generate_builds_credentials_from_the_school_template(): void
    {
        $this->school->update([
            'username_template' => '{nis}',
            'participant_number_template' => '{ruang}-{no}',
            'number_width' => 2,
        ]);

        $venue = $this->prepareVenue($this->school);
        $class = $this->addClass($this->school, $this->academicYear);

        $this->addStudent($this->school, $this->academicYear, $class, '20260001');
        $this->addStudent($this->school, $this->academicYear, $class, '20260002');

        $this->post(route('admin.exam-participants.sync'), [
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'class_ids' => [$class->id],
        ]);

        $this->post(route('admin.exam-participants.generate'), [
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
        ])->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionHas('toast.type', 'success');

        $participants = ExamParticipant::query()->get();

        $this->assertCount(2, $participants);

        foreach ($participants as $participant) {
            $registration = StudentRegistration::query()
                ->where('student_id', $participant->student_id)
                ->firstOrFail();

            $this->assertSame($registration->nis, $participant->username);
            $this->assertStringStartsWith($venue['room']->code.'-', $participant->participant_number);
            $this->assertNotNull($participant->password_plain);
            $this->assertTrue(Hash::check($participant->password_plain, $participant->password));
        }

        $numbers = $participants->pluck('participant_number')->sort()->values()->all();

        $this->assertSame([
            $venue['room']->code.'-01',
            $venue['room']->code.'-02',
        ], $numbers);
    }

    public function test_generate_keeps_existing_credentials_unless_overwrite_is_requested(): void
    {
        $this->school->update([
            'username_template' => '{nis}',
            'participant_number_template' => '{ruang}-{no}',
            'number_width' => 2,
        ]);

        $venue = $this->prepareVenue($this->school);
        $class = $this->addClass($this->school, $this->academicYear);
        $student = $this->addStudent($this->school, $this->academicYear, $class, '20260001');

        $participant = $this->makeParticipant($student, $this->school, $this->academicYear, [
            'room_id' => $venue['room']->id,
            'session_id' => $venue['session']->id,
            'username' => 'LAMA',
            'participant_number' => 'X-01',
            'password' => 'lama',
            'password_plain' => 'lama',
        ]);

        $this->post(route('admin.exam-participants.generate'), [
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
        ]);

        $participant->refresh();

        $this->assertSame('LAMA', $participant->username);
        $this->assertSame('X-01', $participant->participant_number);

        $this->post(route('admin.exam-participants.generate'), [
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'overwrite' => true,
        ]);

        $participant->refresh();

        $this->assertSame('20260001', $participant->username);
        $this->assertSame($venue['room']->code.'-01', $participant->participant_number);
        $this->assertNotSame('LAMA', $participant->password_plain);
    }

    public function test_generate_resolves_duplicate_values_with_a_suffix(): void
    {
        $this->school->update([
            'username_template' => '{sesi}',
            'participant_number_template' => '{sesi}',
            'number_width' => 2,
        ]);

        $venue = $this->prepareVenue($this->school);
        $class = $this->addClass($this->school, $this->academicYear);

        $this->addStudent($this->school, $this->academicYear, $class, '20260001');
        $this->addStudent($this->school, $this->academicYear, $class, '20260002');

        $this->post(route('admin.exam-participants.sync'), [
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'class_ids' => [$class->id],
        ]);

        $this->post(route('admin.exam-participants.generate'), [
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
        ]);

        $code = strtoupper($venue['session']->code);

        $this->assertSame([
            $code,
            $code.'-2',
        ], ExamParticipant::query()->pluck('username')->sort()->values()->all());

        $this->assertSame([
            $code,
            $code.'-2',
        ], ExamParticipant::query()->pluck('participant_number')->sort()->values()->all());
    }

    public function test_generate_reports_when_there_are_no_participants(): void
    {
        $this->post(route('admin.exam-participants.generate'), [
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
        ])->assertRedirect()->assertSessionHas('toast.type', 'warning');
    }

    public function test_update_changes_credential_values(): void
    {
        $participant = $this->singleParticipant();

        $this->put(route('admin.exam-participants.update', $participant->id), [
            'username' => 'USER-BARU',
            'participant_number' => 'A-09',
        ])->assertRedirect()->assertSessionHas('toast.type', 'success');

        $participant->refresh();

        $this->assertSame('USER-BARU', $participant->username);
        $this->assertSame('A-09', $participant->participant_number);
    }

    public function test_update_changes_room_and_session(): void
    {
        $venue = $this->prepareVenue($this->school);
        $participant = $this->singleParticipant();

        $this->put(route('admin.exam-participants.update', $participant->id), [
            'room_id' => $venue['room']->id,
            'session_id' => $venue['session']->id,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $participant->refresh();

        $this->assertSame($venue['room']->id, $participant->room_id);
        $this->assertSame($venue['session']->id, $participant->session_id);
    }

    public function test_update_rejects_username_used_by_another_participant_in_the_same_year(): void
    {
        $class = $this->addClass($this->school, $this->academicYear);

        $first = $this->makeParticipant(
            $this->addStudent($this->school, $this->academicYear, $class, '20260001'),
            $this->school,
            $this->academicYear,
            ['username' => 'SAMA']
        );

        $second = $this->makeParticipant(
            $this->addStudent($this->school, $this->academicYear, $class, '20260002'),
            $this->school,
            $this->academicYear
        );

        $this->put(route('admin.exam-participants.update', $second->id), [
            'username' => 'SAMA',
        ])->assertSessionHasErrors('username');

        $this->assertSame('SAMA', $first->fresh()->username);
        $this->assertNotSame('SAMA', $second->fresh()->username);
    }

    public function test_update_rejects_room_from_another_school(): void
    {
        $participant = $this->singleParticipant();
        $otherRoom = Room::factory()->create();

        $this->put(route('admin.exam-participants.update', $participant->id), [
            'room_id' => $otherRoom->id,
        ])->assertSessionHasErrors('room_id');

        $this->assertNull($participant->fresh()->room_id);
    }

    public function test_bulk_placement_moves_only_the_selected_participants(): void
    {
        $venue = $this->prepareVenue($this->school);
        $class = $this->addClass($this->school, $this->academicYear);

        $selected = $this->makeParticipant(
            $this->addStudent($this->school, $this->academicYear, $class, '20260001'),
            $this->school,
            $this->academicYear
        );

        $untouched = $this->makeParticipant(
            $this->addStudent($this->school, $this->academicYear, $class, '20260002'),
            $this->school,
            $this->academicYear
        );

        $this->patch(route('admin.exam-participants.bulk-placement'), [
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'participant_ids' => [$selected->id],
            'room_id' => $venue['room']->id,
            'session_id' => $venue['session']->id,
        ])->assertRedirect()->assertSessionHas('toast.type', 'success');

        $selected->refresh();
        $untouched->refresh();

        $this->assertSame($venue['room']->id, $selected->room_id);
        $this->assertSame($venue['session']->id, $selected->session_id);
        $this->assertNull($untouched->room_id);
        $this->assertNull($untouched->session_id);
    }

    public function test_bulk_placement_rejects_an_empty_selection(): void
    {
        $venue = $this->prepareVenue($this->school);

        $this->patch(route('admin.exam-participants.bulk-placement'), [
            'school_id' => $this->school->id,
            'room_id' => $venue['room']->id,
            'session_id' => $venue['session']->id,
        ])->assertSessionHasErrors('participant_ids');
    }

    public function test_bulk_placement_rejects_room_from_another_school(): void
    {
        $venue = $this->prepareVenue($this->school);
        $participant = $this->singleParticipant();
        $otherRoom = Room::factory()->create();

        $this->patch(route('admin.exam-participants.bulk-placement'), [
            'school_id' => $this->school->id,
            'participant_ids' => [$participant->id],
            'room_id' => $otherRoom->id,
            'session_id' => $venue['session']->id,
        ])->assertSessionHasErrors('room_id');

        $participant->refresh();

        $this->assertNull($participant->room_id);
        $this->assertNull($participant->session_id);
    }

    public function test_bulk_placement_does_not_move_participants_of_another_school(): void
    {
        $venue = $this->prepareVenue($this->school);
        $otherSchool = School::factory()->create();
        $foreign = $this->makeParticipant(
            $this->addStudent($otherSchool, $this->academicYear, $this->addClass($otherSchool, $this->academicYear), '20260001'),
            $otherSchool,
            $this->academicYear
        );

        $this->patch(route('admin.exam-participants.bulk-placement'), [
            'school_id' => $this->school->id,
            'participant_ids' => [$foreign->id],
            'room_id' => $venue['room']->id,
            'session_id' => $venue['session']->id,
        ])->assertSessionHasErrors('participant_ids');

        $foreign->refresh();

        $this->assertNull($foreign->room_id);
        $this->assertNull($foreign->session_id);
    }

    public function test_reset_password_replaces_both_password_columns(): void
    {
        $participant = $this->singleParticipant(['password' => 'lama', 'password_plain' => 'lama']);

        $this->patch(route('admin.exam-participants.reset-password', $participant->id))
            ->assertRedirect()
            ->assertSessionHas('toast.type', 'success');

        $participant->refresh();

        $this->assertNotSame('lama', $participant->password_plain);
        $this->assertTrue(Hash::check($participant->password_plain, $participant->password));
    }

    public function test_school_credential_template_can_be_updated(): void
    {
        $this->patch(route('admin.schools.credential-template', $this->school->id), [
            'username_template' => '{nis}-{no}',
            'participant_number_template' => '{ruang}-{no}',
            'number_width' => 4,
        ])->assertRedirect()->assertSessionHas('toast.type', 'success');

        $this->assertDatabaseHas('schools', [
            'id' => $this->school->id,
            'username_template' => '{nis}-{no}',
            'participant_number_template' => '{ruang}-{no}',
            'number_width' => 4,
        ]);
    }

    public function test_credential_template_rejects_an_unknown_placeholder(): void
    {
        $this->patch(route('admin.schools.credential-template', $this->school->id), [
            'username_template' => '{xyz}',
            'participant_number_template' => '{no}',
            'number_width' => 3,
        ])->assertSessionHasErrors('username_template');
    }

    public function test_credential_template_rejects_a_template_without_placeholder(): void
    {
        $this->patch(route('admin.schools.credential-template', $this->school->id), [
            'username_template' => 'SISWA',
            'participant_number_template' => '{no}',
            'number_width' => 3,
        ])->assertSessionHasErrors('username_template');
    }

    public function test_destroy_removes_the_participant(): void
    {
        $participant = $this->singleParticipant();

        $this->delete(route('admin.exam-participants.destroy', $participant->id))
            ->assertRedirect()
            ->assertSessionHas('toast.type', 'success');

        $this->assertDatabaseMissing('exam_participants', ['id' => $participant->id]);
    }

    public function test_cards_returns_a_pdf_document(): void
    {
        $participant = $this->singleParticipant([
            'username' => '20260001',
            'participant_number' => 'A-01',
            'password_plain' => 'RAHASIA1',
            'password' => 'rahasia1',
        ]);

        $response = $this->get(route('admin.exam-participants.cards', [
            'school_id' => $participant->school_id,
            'academic_year_id' => $participant->academic_year_id,
        ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_cards_requires_an_accessible_school(): void
    {
        $this->get(route('admin.exam-participants.cards'))
            ->assertForbidden();
    }

    public function test_database_rejects_a_second_credential_for_the_same_student_school_and_year(): void
    {
        $participant = $this->singleParticipant();

        $this->expectException(UniqueConstraintViolationException::class);

        ExamParticipant::factory()
            ->forSchoolYear($this->school, $this->academicYear, $participant->student)
            ->create();
    }

    public function test_guru_is_forbidden_from_the_participant_module(): void
    {
        $this->actingAs(User::factory()->guru()->create());

        $this->get(route('admin.exam-participants.index'))->assertForbidden();

        $class = $this->addClass($this->school, $this->academicYear);

        $this->post(route('admin.exam-participants.sync'), [
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'class_ids' => [$class->id],
        ])->assertForbidden();
    }

    public function test_headmaster_can_view_but_cannot_manage_participants(): void
    {
        $headmaster = User::factory()->guru()->create();
        $this->school->update(['headmaster_id' => $headmaster->id]);
        $this->actingAs($headmaster);

        $class = $this->addClass($this->school, $this->academicYear);
        $this->makeParticipant(
            $this->addStudent($this->school, $this->academicYear, $class, '20260001'),
            $this->school,
            $this->academicYear
        );

        $this->get(route('admin.exam-participants.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('participants.data', 1));

        $this->post(route('admin.exam-participants.generate'), [
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
        ])->assertForbidden();
    }

    public function test_school_admin_cannot_manage_participants_of_another_school(): void
    {
        $schoolAdmin = User::factory()->guru()->create();
        $this->school->update(['admin_id' => $schoolAdmin->id]);

        $otherSchool = School::factory()->create();
        $class = $this->addClass($otherSchool, $this->academicYear);

        $this->actingAs($schoolAdmin)
            ->post(route('admin.exam-participants.sync'), [
                'school_id' => $otherSchool->id,
                'academic_year_id' => $this->academicYear->id,
                'class_ids' => [$class->id],
            ])
            ->assertForbidden();

        $this->assertSame(0, ExamParticipant::query()->count());
    }

    /**
     * Create a class inside the given school and academic year.
     */
    private function addClass(School $school, AcademicYear $academicYear): SchoolClass
    {
        return SchoolClass::factory()->create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
        ]);
    }

    /**
     * Register a student with a NIS and enroll them into the given class.
     */
    private function addStudent(
        School $school,
        AcademicYear $academicYear,
        SchoolClass $class,
        string $nis,
    ): Student {
        $student = Student::factory()->create();

        StudentRegistration::factory()->create([
            'student_id' => $student->id,
            'school_id' => $school->id,
            'nis' => $nis,
        ]);

        ClassMember::factory()->create([
            'class_id' => $class->id,
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
        ]);

        return $student;
    }

    /**
     * Create an exam participant directly.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function makeParticipant(
        Student $student,
        School $school,
        AcademicYear $academicYear,
        array $attributes = [],
    ): ExamParticipant {
        return ExamParticipant::factory()
            ->forSchoolYear($school, $academicYear, $student)
            ->create($attributes);
    }

    /**
     * Create one participant together with its class membership.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function singleParticipant(array $attributes = []): ExamParticipant
    {
        $class = $this->addClass($this->school, $this->academicYear);
        $student = $this->addStudent($this->school, $this->academicYear, $class, '20260001');

        return $this->makeParticipant($student, $this->school, $this->academicYear, $attributes);
    }

    /**
     * Make sure the school has an active room and session.
     *
     * @return array{room: Room, session: Session}
     */
    private function prepareVenue(School $school): array
    {
        $room = Room::query()
            ->where('school_id', $school->id)
            ->where('is_active', true)
            ->first()
            ?? Room::factory()->forSchool($school)->create(['is_active' => true]);

        $session = Session::query()
            ->where('school_id', $school->id)
            ->where('is_active', true)
            ->first()
            ?? Session::factory()->forSchool($school)->create(['is_active' => true]);

        return ['room' => $room, 'session' => $session];
    }
}
