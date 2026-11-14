<?php

namespace Tests\Feature\Admin;

use App\Enums\RegistrationStatus;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StudentRegistrationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->administrator()->create());
    }

    public function test_index_lists_all_registrations_of_the_student(): void
    {
        $academicYear = AcademicYear::factory()->create(['is_active' => true]);
        $firstSchool = School::factory()->create();
        $secondSchool = School::factory()->create();

        $student = Student::factory()->create(['name' => 'Budi Santoso']);

        StudentRegistration::factory()
            ->forStudent($student)
            ->forSchool($firstSchool, $academicYear)
            ->create(['status' => RegistrationStatus::Pindah]);
        StudentRegistration::factory()
            ->forStudent($student)
            ->forSchool($secondSchool, $academicYear)
            ->create(['status' => RegistrationStatus::Aktif]);

        $this->get(route('admin.student-registrations.index', $student))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Students/Registrations')
                ->where('student.name', 'Budi Santoso')
                ->has('registrations', 2)
                ->has('schools')
                ->has('academicYears')
                ->where('canManage', true)
            );
    }

    public function test_store_creates_a_registration_and_marks_the_previous_active_registration_as_transferred(): void
    {
        $academicYear = AcademicYear::factory()->create(['is_active' => true]);
        $inactiveAcademicYear = AcademicYear::factory()->create(['is_active' => false]);
        $oldSchool = School::factory()->create();
        $newSchool = School::factory()->create();

        $student = Student::factory()->create();
        $oldRegistration = StudentRegistration::factory()
            ->forStudent($student)
            ->forSchool($oldSchool, $academicYear)
            ->create(['status' => RegistrationStatus::Aktif]);

        $this->post(route('admin.student-registrations.store', $student), [
            'school_id' => $newSchool->id,
            'nis' => 'NIS-BARU',
            'entry_academic_year_id' => $inactiveAcademicYear->id,
            'status' => RegistrationStatus::Aktif->value,
        ])->assertRedirect()->assertSessionHas('toast');

        $this->assertSame(RegistrationStatus::Pindah, $oldRegistration->fresh()->status);
        $this->assertSame($academicYear->id, $oldRegistration->fresh()->exit_academic_year_id);

        $this->assertDatabaseHas('student_registrations', [
            'student_id' => $student->id,
            'school_id' => $newSchool->id,
            'nis' => 'NIS-BARU',
            'entry_academic_year_id' => $academicYear->id,
            'status' => RegistrationStatus::Aktif->value,
        ]);

        $this->assertSame(
            1,
            $student->registrations()->where('status', RegistrationStatus::Aktif->value)->count()
        );
    }

    public function test_store_requires_exit_year_when_status_is_not_active(): void
    {
        AcademicYear::factory()->create(['is_active' => true]);
        $student = Student::factory()->create();
        $school = School::factory()->create();

        $this->post(route('admin.student-registrations.store', $student), [
            'school_id' => $school->id,
            'nis' => 'NIS-BARU',
            'status' => RegistrationStatus::Lulus->value,
        ])->assertSessionHasErrors('exit_academic_year_id');

        $this->assertDatabaseCount('student_registrations', 0);
    }

    public function test_store_saves_exit_year_for_an_inactive_registration(): void
    {
        AcademicYear::factory()->create(['is_active' => true]);
        $exitAcademicYear = AcademicYear::factory()->create(['is_active' => false]);
        $student = Student::factory()->create();
        $school = School::factory()->create();

        $this->post(route('admin.student-registrations.store', $student), [
            'school_id' => $school->id,
            'nis' => 'NIS-BARU',
            'status' => RegistrationStatus::Lulus->value,
            'exit_academic_year_id' => $exitAcademicYear->id,
        ])->assertRedirect()->assertSessionHas('toast');

        $this->assertDatabaseHas('student_registrations', [
            'student_id' => $student->id,
            'school_id' => $school->id,
            'status' => RegistrationStatus::Lulus->value,
            'exit_academic_year_id' => $exitAcademicYear->id,
        ]);
    }

    public function test_store_requires_an_active_academic_year(): void
    {
        AcademicYear::factory()->create(['is_active' => false]);

        $student = Student::factory()->create();
        $school = School::factory()->create();

        $this->post(route('admin.student-registrations.store', $student), [
            'school_id' => $school->id,
            'nis' => 'NIS-BARU',
            'status' => RegistrationStatus::Aktif->value,
        ])->assertRedirect()->assertSessionHas('toast');

        $this->assertDatabaseCount('student_registrations', 0);
    }

    public function test_store_rejects_a_second_registration_for_the_same_school(): void
    {
        $academicYear = AcademicYear::factory()->create(['is_active' => true]);
        $school = School::factory()->create();

        $student = Student::factory()->create();
        StudentRegistration::factory()
            ->forStudent($student)
            ->forSchool($school, $academicYear)
            ->create();

        $this->post(route('admin.student-registrations.store', $student), [
            'school_id' => $school->id,
            'nis' => 'NIS-LAIN',
            'entry_academic_year_id' => $academicYear->id,
            'status' => RegistrationStatus::Aktif->value,
        ])->assertSessionHasErrors('school_id');
    }

    public function test_store_rejects_a_duplicate_nis_in_the_school(): void
    {
        $academicYear = AcademicYear::factory()->create(['is_active' => true]);
        $school = School::factory()->create();

        StudentRegistration::factory()
            ->forSchool($school, $academicYear)
            ->create(['nis' => 'NIS-SAMA']);

        $student = Student::factory()->create();

        $this->post(route('admin.student-registrations.store', $student), [
            'school_id' => $school->id,
            'nis' => 'NIS-SAMA',
            'entry_academic_year_id' => $academicYear->id,
            'status' => RegistrationStatus::Aktif->value,
        ])->assertSessionHasErrors('nis');
    }

    public function test_update_can_reactivate_a_registration_and_deactivates_the_other_one(): void
    {
        $academicYear = AcademicYear::factory()->create(['is_active' => true]);
        $firstSchool = School::factory()->create();
        $secondSchool = School::factory()->create();

        $student = Student::factory()->create();
        $firstRegistration = StudentRegistration::factory()
            ->forStudent($student)
            ->forSchool($firstSchool, $academicYear)
            ->create(['status' => RegistrationStatus::Pindah]);
        $secondRegistration = StudentRegistration::factory()
            ->forStudent($student)
            ->forSchool($secondSchool, $academicYear)
            ->create(['status' => RegistrationStatus::Aktif]);

        $this->put(
            route('admin.student-registrations.update', [
                'student' => $student,
                'registration' => $firstRegistration,
            ]),
            [
                'school_id' => $firstSchool->id,
                'nis' => $firstRegistration->nis,
                'entry_academic_year_id' => $academicYear->id,
                'status' => RegistrationStatus::Aktif->value,
            ]
        )->assertRedirect()->assertSessionHas('toast');

        $this->assertSame(RegistrationStatus::Aktif, $firstRegistration->fresh()->status);
        $this->assertSame(RegistrationStatus::Pindah, $secondRegistration->fresh()->status);
    }

    public function test_update_requires_exit_year_when_status_is_not_active(): void
    {
        $academicYear = AcademicYear::factory()->create(['is_active' => true]);
        $school = School::factory()->create();
        $student = Student::factory()->create();
        $registration = StudentRegistration::factory()
            ->forStudent($student)
            ->forSchool($school, $academicYear)
            ->create(['status' => RegistrationStatus::Aktif]);

        $this->put(
            route('admin.student-registrations.update', [
                'student' => $student,
                'registration' => $registration,
            ]),
            [
                'school_id' => $school->id,
                'nis' => $registration->nis,
                'entry_academic_year_id' => $academicYear->id,
                'status' => RegistrationStatus::Lulus->value,
            ]
        )->assertSessionHasErrors('exit_academic_year_id');

        $this->assertSame(RegistrationStatus::Aktif, $registration->fresh()->status);
    }

    public function test_update_saves_exit_year_and_clears_it_when_reactivated(): void
    {
        $academicYear = AcademicYear::factory()->create(['is_active' => true]);
        $exitAcademicYear = AcademicYear::factory()->create(['is_active' => false]);
        $school = School::factory()->create();
        $student = Student::factory()->create();
        $registration = StudentRegistration::factory()
            ->forStudent($student)
            ->forSchool($school, $academicYear)
            ->create(['status' => RegistrationStatus::Aktif]);

        $this->put(
            route('admin.student-registrations.update', [
                'student' => $student,
                'registration' => $registration,
            ]),
            [
                'school_id' => $school->id,
                'nis' => $registration->nis,
                'entry_academic_year_id' => $academicYear->id,
                'status' => RegistrationStatus::Lulus->value,
                'exit_academic_year_id' => $exitAcademicYear->id,
            ]
        )->assertRedirect()->assertSessionHas('toast');

        $this->assertSame($exitAcademicYear->id, $registration->fresh()->exit_academic_year_id);

        $this->put(
            route('admin.student-registrations.update', [
                'student' => $student,
                'registration' => $registration,
            ]),
            [
                'school_id' => $school->id,
                'nis' => $registration->nis,
                'entry_academic_year_id' => $academicYear->id,
                'status' => RegistrationStatus::Aktif->value,
                'exit_academic_year_id' => $exitAcademicYear->id,
            ]
        )->assertRedirect()->assertSessionHas('toast');

        $this->assertNull($registration->fresh()->exit_academic_year_id);
    }

    public function test_update_rejects_a_registration_belonging_to_another_student(): void
    {
        $academicYear = AcademicYear::factory()->create(['is_active' => true]);
        $school = School::factory()->create();

        $student = Student::factory()->create();
        $otherStudent = Student::factory()->create();

        $registration = StudentRegistration::factory()
            ->forStudent($otherStudent)
            ->forSchool($school, $academicYear)
            ->create();

        $this->put(
            route('admin.student-registrations.update', [
                'student' => $student,
                'registration' => $registration,
            ]),
            [
                'school_id' => $school->id,
                'nis' => $registration->nis,
                'entry_academic_year_id' => $academicYear->id,
                'status' => RegistrationStatus::Aktif->value,
            ]
        )->assertNotFound();
    }

    public function test_destroy_rejects_an_active_registration(): void
    {
        $student = Student::factory()->create();
        $registration = StudentRegistration::factory()
            ->forStudent($student)
            ->create(['status' => RegistrationStatus::Aktif]);

        $this->delete(route('admin.student-registrations.destroy', [
            'student' => $student,
            'registration' => $registration,
        ]))->assertRedirect()->assertSessionHas('toast');

        $this->assertDatabaseHas('student_registrations', ['id' => $registration->id]);
    }

    public function test_destroy_deletes_an_inactive_registration(): void
    {
        $student = Student::factory()->create();
        $registration = StudentRegistration::factory()
            ->forStudent($student)
            ->create(['status' => RegistrationStatus::Lulus]);

        $this->delete(route('admin.student-registrations.destroy', [
            'student' => $student,
            'registration' => $registration,
        ]))->assertRedirect()->assertSessionHas('toast');

        $this->assertDatabaseMissing('student_registrations', ['id' => $registration->id]);
    }

    public function test_guru_cannot_access_registrations(): void
    {
        $student = Student::factory()->create();

        $this->actingAs(User::factory()->guru()->create())
            ->get(route('admin.student-registrations.index', $student))
            ->assertForbidden();
    }

    public function test_school_admin_cannot_access_registrations_of_a_student_from_another_school(): void
    {
        $schoolAdmin = User::factory()->guru()->create();
        School::factory()->create(['admin_id' => $schoolAdmin->id]);
        $otherSchool = School::factory()->create();
        $academicYear = AcademicYear::factory()->create(['is_active' => true]);

        $student = Student::factory()->create();
        StudentRegistration::factory()
            ->forStudent($student)
            ->forSchool($otherSchool, $academicYear)
            ->create();

        $this->actingAs($schoolAdmin)
            ->get(route('admin.student-registrations.index', $student))
            ->assertForbidden();
    }

    public function test_available_lists_existing_students_not_registered_at_the_target_school(): void
    {
        $academicYear = AcademicYear::factory()->create(['is_active' => true]);
        $originSchool = School::factory()->create();
        $targetSchool = School::factory()->create();

        $student = Student::factory()->create(['name' => 'Budi Santoso', 'nisn' => '1111111111']);
        StudentRegistration::factory()
            ->forStudent($student)
            ->forSchool($originSchool, $academicYear)
            ->create(['status' => RegistrationStatus::Aktif, 'nis' => 'ASAL-001']);

        $alreadyRegistered = Student::factory()->create(['name' => 'Budi Target']);
        StudentRegistration::factory()
            ->forStudent($alreadyRegistered)
            ->forSchool($targetSchool, $academicYear)
            ->create(['status' => RegistrationStatus::Aktif, 'nis' => 'TARGET-001']);

        $this->getJson(route('admin.student-registrations.available', [
            'school_id' => $targetSchool->id,
            'search' => 'Budi',
        ]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $student->id)
            ->assertJsonPath('data.0.current_school_name', $originSchool->name)
            ->assertJsonPath('data.0.current_status', RegistrationStatus::Aktif->value)
            ->assertJsonPath('data.0.has_active_registration', true);
    }

    public function test_available_is_scoped_to_school_admin_accessible_students(): void
    {
        $schoolAdmin = User::factory()->guru()->create();
        $ownSchool = School::factory()->create(['admin_id' => $schoolAdmin->id]);
        $targetSchool = School::factory()->create();
        $otherSchool = School::factory()->create();

        $schoolAdmin->update(['school_id' => $targetSchool->id]);

        $academicYear = AcademicYear::factory()->create(['is_active' => true]);

        $ownStudent = Student::factory()->create(['name' => 'Siswa Sendiri']);
        StudentRegistration::factory()
            ->forStudent($ownStudent)
            ->forSchool($ownSchool, $academicYear)
            ->create();

        $otherStudent = Student::factory()->create(['name' => 'Siswa Lain']);
        StudentRegistration::factory()
            ->forStudent($otherStudent)
            ->forSchool($otherSchool, $academicYear)
            ->create();

        $this->actingAs($schoolAdmin)
            ->getJson(route('admin.student-registrations.available', [
                'school_id' => $targetSchool->id,
                'search' => 'Siswa',
            ]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownStudent->id);

        $this->actingAs($schoolAdmin)
            ->getJson(route('admin.student-registrations.available', [
                'school_id' => $otherSchool->id,
                'search' => 'Siswa',
            ]))
            ->assertForbidden();
    }

    public function test_guru_cannot_search_students_to_pull(): void
    {
        $school = School::factory()->create();

        $this->actingAs(User::factory()->guru()->create())
            ->getJson(route('admin.student-registrations.available', [
                'school_id' => $school->id,
                'search' => 'Budi',
            ]))
            ->assertForbidden();
    }
}
