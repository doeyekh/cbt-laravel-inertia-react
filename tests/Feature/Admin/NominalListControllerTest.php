<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicYear;
use App\Models\ClassMember;
use App\Models\ExamParticipant;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NominalListControllerTest extends TestCase
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
            ->get(route('admin.nominal-list.index'))
            ->assertForbidden();
    }

    public function test_index_lists_participants_per_class(): void
    {
        $schoolClass = SchoolClass::factory()
            ->forSchool($this->school, $this->academicYear->id)
            ->active()
            ->create();

        $student1 = Student::factory()->create(['name' => 'Siswa Satu', 'nisn' => '10000001']);
        $student2 = Student::factory()->create(['name' => 'Siswa Dua', 'nisn' => '10000002']);

        ClassMember::factory()->forClass($schoolClass, $student1)->create();
        ClassMember::factory()->forClass($schoolClass, $student2)->create();

        ExamParticipant::factory()
            ->forSchoolYear($this->school, $this->academicYear, $student1)
            ->create();
        ExamParticipant::factory()
            ->forSchoolYear($this->school, $this->academicYear, $student2)
            ->create();

        $this->actingAs($this->admin)
            ->get(route('admin.nominal-list.index', [
                'class_id' => $schoolClass->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/NominalList/Index')
                ->has('participants.data', 2)
                ->where('participants.data.0.name', 'Siswa Satu')
                ->where('participants.data.1.name', 'Siswa Dua'));
    }

    public function test_index_filters_by_school_and_academic_year(): void
    {
        $class = SchoolClass::factory()
            ->forSchool($this->school, $this->academicYear->id)
            ->active()
            ->create();
        $student = Student::factory()->create(['name' => 'Siswa A', 'nisn' => '20000001']);
        ClassMember::factory()->forClass($class, $student)->create();
        ExamParticipant::factory()
            ->forSchoolYear($this->school, $this->academicYear, $student)
            ->create();

        $otherYear = AcademicYear::factory()->create();
        $otherStudent = Student::factory()->create(['name' => 'Siswa B', 'nisn' => '20000002']);
        $otherClass = SchoolClass::factory()
            ->forSchool($this->school, $otherYear->id)
            ->active()
            ->create();
        ClassMember::factory()->forClass($otherClass, $otherStudent)->create();
        ExamParticipant::factory()
            ->forSchoolYear($this->school, $otherYear, $otherStudent)
            ->create();

        $this->actingAs($this->admin)
            ->get(route('admin.nominal-list.index', [
                'school_id' => $this->school->id,
                'academic_year_id' => $this->academicYear->id,
            ]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('participants.data', 1)
                ->where('participants.data.0.name', 'Siswa A'));
    }

    public function test_pdf_returns_pdf_document(): void
    {
        $schoolClass = SchoolClass::factory()
            ->forSchool($this->school, $this->academicYear->id)
            ->active()
            ->create();
        $student = Student::factory()->create(['name' => 'Siswa PDF', 'nisn' => '30000001']);
        ClassMember::factory()->forClass($schoolClass, $student)->create();
        ExamParticipant::factory()
            ->forSchoolYear($this->school, $this->academicYear, $student)
            ->create();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.nominal-list.pdf', [
                'class_id' => $schoolClass->id,
            ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }
}
