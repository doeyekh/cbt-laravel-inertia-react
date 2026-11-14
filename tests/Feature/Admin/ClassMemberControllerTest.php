<?php

namespace Tests\Feature\Admin;

use App\Enums\RegistrationStatus;
use App\Models\AcademicYear;
use App\Models\ClassMember;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ClassMemberControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->administrator()->create());
    }

    public function test_index_lists_the_members_of_the_class(): void
    {
        [$school, $academicYear, $schoolClass] = $this->createClassContext();

        $male = $this->createStudentWithRegistration($school, $academicYear, 'Budi Santoso', 'L');
        $female = $this->createStudentWithRegistration($school, $academicYear, 'Siti Aminah', 'P');

        ClassMember::factory()->forClass($schoolClass, $male)->create();
        ClassMember::factory()->forClass($schoolClass, $female)->create();

        $this->get(route('admin.class-members.index', $schoolClass))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ClassMembers/Index')
                ->where('schoolClass.name', $schoolClass->name)
                ->has('members.data', 2)
                ->where('stats.total', 2)
                ->where('stats.male', 1)
                ->where('stats.female', 1)
                ->where('canManage', true)
            );
    }

    public function test_index_can_search_members_by_name(): void
    {
        [$school, $academicYear, $schoolClass] = $this->createClassContext();

        $male = $this->createStudentWithRegistration($school, $academicYear, 'Budi Santoso', 'L');
        $female = $this->createStudentWithRegistration($school, $academicYear, 'Siti Aminah', 'P');

        ClassMember::factory()->forClass($schoolClass, $male)->create();
        ClassMember::factory()->forClass($schoolClass, $female)->create();

        $this->get(route('admin.class-members.index', [
            'schoolClass' => $schoolClass,
            'search' => 'Budi',
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('members.data', 1)
                ->where('members.data.0.name', 'Budi Santoso')
            );
    }

    public function test_guru_cannot_access_class_members(): void
    {
        [, , $schoolClass] = $this->createClassContext();

        $this->actingAs(User::factory()->guru()->create())
            ->get(route('admin.class-members.index', $schoolClass))
            ->assertForbidden();
    }

    public function test_available_returns_only_students_that_can_join_the_class(): void
    {
        [$school, $academicYear, $schoolClass] = $this->createClassContext();

        $eligible = $this->createStudentWithRegistration($school, $academicYear, 'Budi Eligible', 'L');

        $alreadyAssigned = $this->createStudentWithRegistration($school, $academicYear, 'Budi Assigned', 'L');
        ClassMember::factory()->forClass($schoolClass, $alreadyAssigned)->create();

        $inactive = $this->createStudentWithRegistration(
            $school,
            $academicYear,
            'Budi Inactive',
            'L',
            RegistrationStatus::Pindah
        );

        $otherSchool = School::factory()->create();
        $otherSchoolStudent = $this->createStudentWithRegistration(
            $otherSchool,
            $academicYear,
            'Budi Other School',
            'L'
        );

        $response = $this->getJson(route('admin.class-members.available', [
            'schoolClass' => $schoolClass,
            'search' => 'Budi',
        ]))->assertOk();

        $data = $response->json('data');

        $this->assertCount(1, $data);
        $this->assertSame($eligible->id, $data[0]['id']);
        $this->assertNotSame($alreadyAssigned->id, $data[0]['id']);
        $this->assertNotSame($inactive->id, $data[0]['id']);
        $this->assertNotSame($otherSchoolStudent->id, $data[0]['id']);
    }

    public function test_available_requires_a_search_term(): void
    {
        [, , $schoolClass] = $this->createClassContext();

        $this->getJson(route('admin.class-members.available', $schoolClass))
            ->assertOk()
            ->assertJson(['data' => []]);
    }

    public function test_store_adds_an_eligible_student_to_the_class(): void
    {
        [$school, $academicYear, $schoolClass] = $this->createClassContext();
        $student = $this->createStudentWithRegistration($school, $academicYear, 'Budi Santoso', 'L');

        $this->post(route('admin.class-members.store', $schoolClass), [
            'student_id' => $student->id,
        ])->assertRedirect()->assertSessionHas('toast');

        $this->assertDatabaseHas('class_members', [
            'class_id' => $schoolClass->id,
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
        ]);
    }

    public function test_store_rejects_a_student_without_an_active_registration_in_the_school(): void
    {
        [$school, $academicYear, $schoolClass] = $this->createClassContext();
        $student = $this->createStudentWithRegistration(
            $school,
            $academicYear,
            'Budi Santoso',
            'L',
            RegistrationStatus::Pindah
        );

        $this->post(route('admin.class-members.store', $schoolClass), [
            'student_id' => $student->id,
        ])->assertSessionHasErrors('student_id');

        $this->assertDatabaseCount('class_members', 0);
    }

    public function test_store_rejects_a_student_already_assigned_to_another_class_in_the_same_year(): void
    {
        [$school, $academicYear, $schoolClass] = $this->createClassContext();
        $otherClass = SchoolClass::factory()->create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
        ]);

        $student = $this->createStudentWithRegistration($school, $academicYear, 'Budi Santoso', 'L');
        ClassMember::factory()->forClass($otherClass, $student)->create();

        $this->post(route('admin.class-members.store', $schoolClass), [
            'student_id' => $student->id,
        ])->assertSessionHasErrors('student_id');

        $this->assertDatabaseCount('class_members', 1);
    }

    public function test_store_rejects_a_student_registered_at_another_school(): void
    {
        [$school, $academicYear, $schoolClass] = $this->createClassContext();
        $otherSchool = School::factory()->create();
        $student = $this->createStudentWithRegistration($otherSchool, $academicYear, 'Budi Santoso', 'L');

        $this->post(route('admin.class-members.store', $schoolClass), [
            'student_id' => $student->id,
        ])->assertSessionHasErrors('student_id');

        $this->assertDatabaseCount('class_members', 0);
    }

    public function test_destroy_removes_a_member_from_the_class(): void
    {
        [$school, $academicYear, $schoolClass] = $this->createClassContext();
        $student = $this->createStudentWithRegistration($school, $academicYear, 'Budi Santoso', 'L');
        $member = ClassMember::factory()->forClass($schoolClass, $student)->create();

        $this->delete(route('admin.class-members.destroy', [
            'schoolClass' => $schoolClass,
            'classMember' => $member,
        ]))->assertRedirect()->assertSessionHas('toast');

        $this->assertDatabaseMissing('class_members', ['id' => $member->id]);
    }

    public function test_destroy_rejects_a_member_that_belongs_to_another_class(): void
    {
        [$school, $academicYear, $schoolClass] = $this->createClassContext();
        $otherClass = SchoolClass::factory()->create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
        ]);

        $student = $this->createStudentWithRegistration($school, $academicYear, 'Budi Santoso', 'L');
        $member = ClassMember::factory()->forClass($otherClass, $student)->create();

        $this->delete(route('admin.class-members.destroy', [
            'schoolClass' => $schoolClass,
            'classMember' => $member,
        ]))->assertNotFound();

        $this->assertDatabaseHas('class_members', ['id' => $member->id]);
    }

    public function test_headmaster_can_view_members_but_cannot_add_them(): void
    {
        $headmaster = User::factory()->guru()->create();
        [$school, $academicYear, $schoolClass] = $this->createClassContext($headmaster);

        $student = $this->createStudentWithRegistration($school, $academicYear, 'Budi Santoso', 'L');

        $this->actingAs($headmaster)
            ->get(route('admin.class-members.index', $schoolClass))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('canManage', false));

        $this->actingAs($headmaster)
            ->post(route('admin.class-members.store', $schoolClass), [
                'student_id' => $student->id,
            ])
            ->assertForbidden();
    }

    public function test_school_admin_cannot_manage_a_class_from_another_school(): void
    {
        $schoolAdmin = User::factory()->guru()->create();
        School::factory()->create(['admin_id' => $schoolAdmin->id]);
        [, , $schoolClass] = $this->createClassContext();

        $this->actingAs($schoolAdmin)
            ->get(route('admin.class-members.index', $schoolClass))
            ->assertForbidden();
    }

    /**
     * Create a school, an academic year and a class in it.
     *
     * @return array{0: School, 1: AcademicYear, 2: SchoolClass}
     */
    private function createClassContext(?User $headmaster = null): array
    {
        $school = School::factory()->create(
            $headmaster !== null ? ['headmaster_id' => $headmaster->id] : []
        );
        $academicYear = AcademicYear::factory()->create(['is_active' => true]);
        $schoolClass = SchoolClass::factory()->create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
        ]);

        return [$school, $academicYear, $schoolClass];
    }

    private function createStudentWithRegistration(
        School $school,
        AcademicYear $academicYear,
        string $name,
        string $gender,
        RegistrationStatus $status = RegistrationStatus::Aktif
    ): Student {
        $student = Student::factory()->create([
            'name' => $name,
            'gender' => $gender,
        ]);

        StudentRegistration::factory()
            ->forStudent($student)
            ->forSchool($school, $academicYear)
            ->create(['status' => $status]);

        return $student;
    }
}
