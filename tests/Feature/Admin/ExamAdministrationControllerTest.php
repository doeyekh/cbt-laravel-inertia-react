<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicYear;
use App\Models\ClassMember;
use App\Models\ExamPackage;
use App\Models\ExamParticipant;
use App\Models\ExamSchedule;
use App\Models\ExamType;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectTeacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExamAdministrationControllerTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $schoolAdmin;

    private AcademicYear $academicYear;

    private ExamType $examType;

    private SchoolClass $schoolClass;

    private Subject $subject;

    private ExamSchedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->schoolAdmin = User::factory()->guru()->create();
        $this->school->update(['admin_id' => $this->schoolAdmin->id]);

        $this->academicYear = AcademicYear::factory()->active()->create();
        $this->examType = ExamType::factory()
            ->active()
            ->create(['school_id' => $this->school->id]);

        $this->schoolClass = SchoolClass::factory()
            ->forSchool($this->school, $this->academicYear->id)
            ->active()
            ->create();

        $this->subject = Subject::factory()->active()->create();

        $examPackage = ExamPackage::factory()
            ->forSchool($this->school, $this->academicYear, $this->examType)
            ->create();

        $this->schedule = ExamSchedule::factory()
            ->forPackage($examPackage)
            ->create([
                'class_id' => $this->schoolClass->id,
                'subject_id' => $this->subject->id,
            ]);

        SubjectTeacher::factory()
            ->forClass(
                $this->schoolClass,
                $this->academicYear,
                $this->subject,
                User::factory()->guru()->create(['name' => 'Guru Pengampu']),
            )
            ->create(['is_active' => true]);
    }

    public function test_school_admin_can_open_the_administration_page(): void
    {
        $this->actingAs($this->schoolAdmin)
            ->get(route('admin.exam-administration.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ExamAdministration/Index')
                ->where('school.id', $this->school->id)
                ->where('activeAcademicYear.id', $this->academicYear->id)
                ->where('filters.school_id', $this->school->id)
                ->where('filters.academic_year_id', $this->academicYear->id)
                ->where('filters.exam_type_id', $this->examType->id)
                ->has('examTypes', 1)
                ->has('subjects', 1)
                ->has('classes', 1));
    }

    public function test_only_active_exam_types_are_listed(): void
    {
        ExamType::factory()
            ->inactive()
            ->create(['school_id' => $this->school->id]);

        $this->actingAs($this->schoolAdmin)
            ->get(route('admin.exam-administration.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('examTypes', 1)
                ->where('examTypes.0.id', $this->examType->id));
    }

    public function test_page_requires_an_active_academic_year(): void
    {
        $this->academicYear->update(['is_active' => false]);

        $this->actingAs($this->schoolAdmin)
            ->get(route('admin.exam-administration.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('activeAcademicYear', null)
                ->where('filters.academic_year_id', null)
                ->has('classes', 0));
    }

    public function test_guru_cannot_open_the_administration_page(): void
    {
        $this->actingAs(User::factory()->guru()->create())
            ->get(route('admin.exam-administration.index'))
            ->assertForbidden();
    }

    public function test_attendance_pdf_returns_a_pdf_document(): void
    {
        $this->addParticipant();

        $response = $this->actingAs($this->schoolAdmin)
            ->get(route('admin.exam-administration.attendance', [
                'school_id' => $this->school->id,
                'academic_year_id' => $this->academicYear->id,
                'exam_type_id' => $this->examType->id,
                'subject_id' => $this->subject->id,
            ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_attendance_requires_a_subject(): void
    {
        $this->actingAs($this->schoolAdmin)
            ->get(route('admin.exam-administration.attendance', [
                'school_id' => $this->school->id,
                'academic_year_id' => $this->academicYear->id,
                'exam_type_id' => $this->examType->id,
            ]))
            ->assertSessionHasErrors('subject_id');
    }

    public function test_practical_grades_pdf_returns_a_pdf_document(): void
    {
        $this->addParticipant();

        $response = $this->actingAs($this->schoolAdmin)
            ->get(route('admin.exam-administration.practical-grades', [
                'school_id' => $this->school->id,
                'academic_year_id' => $this->academicYear->id,
                'exam_type_id' => $this->examType->id,
                'subject_id' => $this->subject->id,
                'criteria' => 'Persiapan, Pelaksanaan, Hasil',
            ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_practical_grades_do_not_require_an_exam_schedule(): void
    {
        $subject = Subject::factory()->active()->create();

        SubjectTeacher::factory()
            ->forClass($this->schoolClass, $this->academicYear, $subject)
            ->create(['is_active' => true]);

        $student = Student::factory()->create(['name' => 'Siswa Praktek']);
        ClassMember::factory()->forClass($this->schoolClass, $student)->create();

        $response = $this->actingAs($this->schoolAdmin)
            ->get(route('admin.exam-administration.practical-grades', [
                'school_id' => $this->school->id,
                'academic_year_id' => $this->academicYear->id,
                'exam_type_id' => $this->examType->id,
                'subject_id' => $subject->id,
            ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_subjects_include_subjects_taught_without_an_exam_schedule(): void
    {
        $subject = Subject::factory()->active()->create();

        SubjectTeacher::factory()
            ->forClass($this->schoolClass, $this->academicYear, $subject)
            ->create(['is_active' => true]);

        $this->actingAs($this->schoolAdmin)
            ->get(route('admin.exam-administration.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('subjects', 2));
    }

    public function test_school_admin_cannot_print_data_from_another_school(): void
    {
        $otherSchool = School::factory()->create();

        $this->actingAs($this->schoolAdmin)
            ->get(route('admin.exam-administration.attendance', [
                'school_id' => $otherSchool->id,
                'academic_year_id' => $this->academicYear->id,
                'exam_type_id' => $this->examType->id,
                'subject_id' => $this->subject->id,
            ]))
            ->assertForbidden();
    }

    private function addParticipant(): void
    {
        $student = Student::factory()->create(['name' => 'Siswa Uji']);

        ClassMember::factory()->forClass($this->schoolClass, $student)->create();

        ExamParticipant::factory()
            ->forSchoolYear($this->school, $this->academicYear, $student)
            ->create();
    }
}
