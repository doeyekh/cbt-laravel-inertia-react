<?php

namespace Tests\Feature\Admin;

use App\Enums\ExamAttemptStatus;
use App\Models\AcademicYear;
use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\ExamParticipant;
use App\Models\ExamSchedule;
use App\Models\ExamType;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LegerGradeControllerTest extends TestCase
{
    use RefreshDatabase;

    private ?AcademicYear $sharedAcademicYear = null;

    private ?ExamType $sharedExamType = null;

    public function test_administrator_can_open_leger(): void
    {
        $this->actingAs(User::factory()->administrator()->create());

        $this->get(route('admin.leger-grades.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Legers/Index')
                ->has('rows')
                ->has('columns')
                ->has('summary'));
    }

    public function test_academic_year_and_exam_type_are_selected_by_default(): void
    {
        $school = School::factory()->create();
        $academicYear = AcademicYear::factory()->active()->create();
        $examType = ExamType::factory()->active()->create([
            'school_id' => $school->id,
        ]);

        $this->actingAs(User::factory()->administrator()->create());

        $this->get(route('admin.leger-grades.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.academic_year_id', $academicYear->id)
                ->where('filters.exam_type_id', $examType->id)
                ->where('filters.required_filters_selected', true)
                ->where('filters.labels.academic_year', $academicYear->name.' - '.$academicYear->semester->label())
                ->where('academicYears.0.semester_label', $academicYear->semester->label()));
    }

    public function test_school_admin_can_open_leger(): void
    {
        $user = User::factory()->guru()->create();
        School::factory()->create(['admin_id' => $user->id]);

        $this->actingAs($user);

        $this->get(route('admin.leger-grades.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Legers/Index'));
    }

    public function test_guru_cannot_open_leger(): void
    {
        $this->actingAs(User::factory()->guru()->create());

        $this->get(route('admin.leger-grades.index'))
            ->assertForbidden();
    }

    public function test_scores_are_averaged_per_subject(): void
    {
        $school = School::factory()->create();
        $subject = Subject::factory()->create(['name' => 'Bahasa Indonesia']);
        $academicYear = AcademicYear::factory()->active()->create();

        $participant = ExamParticipant::factory()
            ->forSchoolYear($school, $academicYear)
            ->create();

        $this->createGradedAttempt($school, $subject, $participant, 80);
        $this->createGradedAttempt($school, $subject, $participant, 60);

        $this->actingAs(User::factory()->administrator()->create());

        $this->get(route('admin.leger-grades.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Legers/Index')
                ->where('rows.0.nisn', $participant->student->nisn)
                ->where('rows.0.scores.'.$subject->id, 70)
                ->where('rows.0.average', 70)
                ->where('rows.0.completed_count', 2)
                ->where('rows.0.passed_count', 1));
    }

    public function test_school_admin_only_sees_own_school_scores(): void
    {
        $ownSchool = School::factory()->create();
        $otherSchool = School::factory()->create();
        $subject = Subject::factory()->create(['name' => 'IPA']);

        $schoolAdmin = User::factory()->guru()->create();
        $ownSchool->update(['admin_id' => $schoolAdmin->id]);

        $this->createGradedAttempt($ownSchool, $subject, null, 90);
        $this->createGradedAttempt($otherSchool, $subject, null, 40);

        $this->actingAs($schoolAdmin);

        $this->get(route('admin.leger-grades.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Legers/Index')
                ->where('filters.school_is_locked', true)
                ->where('filters.labels.school', $ownSchool->name)
                ->where('summary.student_count', 1)
                ->where('summary.average_percentage', 90)
                ->has('schools', 1));
    }

    public function test_administrator_with_school_id_is_scoped_to_own_school(): void
    {
        $ownSchool = School::factory()->create();
        $otherSchool = School::factory()->create();
        $subject = Subject::factory()->create(['name' => 'Matematika']);

        $schoolAdmin = User::factory()->administrator()->create([
            'school_id' => $ownSchool->id,
        ]);

        $ownAttempt = $this->createGradedAttempt($ownSchool, $subject, null, 90);
        $this->createGradedAttempt($otherSchool, $subject, null, 40);

        $ownClass = $ownAttempt->examSchedule->schoolClass->name;

        $this->actingAs($schoolAdmin);

        $this->get(route('admin.leger-grades.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Legers/Index')
                ->where('filters.school_id', $ownSchool->id)
                ->where('filters.school_is_locked', true)
                ->where('filters.labels.school', $ownSchool->name)
                ->where('filters.labels.class', $ownClass)
                ->where('summary.student_count', 1)
                ->where('summary.average_percentage', 90)
                ->has('schools', 1)
                ->where('schools.0.id', $ownSchool->id));
    }

    public function test_school_scoped_administrator_cannot_switch_school_using_query_string(): void
    {
        $ownSchool = School::factory()->create();
        $otherSchool = School::factory()->create();
        $subject = Subject::factory()->create(['name' => 'Bahasa Inggris']);

        $schoolAdmin = User::factory()->administrator()->create([
            'school_id' => $ownSchool->id,
        ]);

        $this->createGradedAttempt($ownSchool, $subject, null, 85);
        $this->createGradedAttempt($otherSchool, $subject, null, 35);

        $this->actingAs($schoolAdmin);

        $this->get(route('admin.leger-grades.index', [
            'school_id' => $otherSchool->id,
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.school_id', $ownSchool->id)
                ->where('filters.school_is_locked', true)
                ->where('summary.student_count', 1)
                ->where('summary.average_percentage', 85));
    }

    public function test_administrator_without_school_sees_all_schools(): void
    {
        $firstSchool = School::factory()->create();
        $secondSchool = School::factory()->create();
        $subject = Subject::factory()->create(['name' => 'Sejarah']);

        $this->createGradedAttempt($firstSchool, $subject, null, 75);
        $this->createGradedAttempt($secondSchool, $subject, null, 65);

        $this->actingAs(User::factory()->administrator()->create());

        $this->get(route('admin.leger-grades.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.school_id', null)
                ->where('filters.school_is_locked', false)
                ->where('filters.labels.school', 'Semua sekolah')
                ->where('summary.student_count', 2)
                ->has('schools', 2));
    }

    public function test_ungraded_attempts_are_ignored(): void
    {
        $school = School::factory()->create();
        $subject = Subject::factory()->create(['name' => 'Matematika']);
        $academicYear = AcademicYear::factory()->active()->create();

        $participant = ExamParticipant::factory()
            ->forSchoolYear($school, $academicYear)
            ->create();

        $package = ExamPackage::factory()->forSchool($school, $academicYear)->create();
        $schedule = ExamSchedule::factory()->forPackage($package)->create([
            'subject_id' => $subject->id,
        ]);

        ExamAttempt::factory()->create([
            'exam_participant_id' => $participant->id,
            'exam_schedule_id' => $schedule->id,
            'exam_package_id' => $package->id,
            'status' => ExamAttemptStatus::InProgress,
            'score' => null,
            'max_score' => null,
        ]);

        $this->actingAs(User::factory()->administrator()->create());

        $this->get(route('admin.leger-grades.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('summary.student_count', 0)
                ->where('rows', []));
    }

    public function test_pdf_and_excel_exports_are_available(): void
    {
        $this->actingAs(User::factory()->administrator()->create());

        $this->get(route('admin.leger-grades.pdf'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->get(route('admin.leger-grades.export'))
            ->assertOk();
    }

    private function createGradedAttempt(
        School $school,
        Subject $subject,
        ?ExamParticipant $participant = null,
        float $score = 80,
    ): ExamAttempt {
        $academicYear = $participant?->academicYear
            ?? $this->sharedAcademicYear();

        $package = ExamPackage::factory()
            ->forSchool($school, $academicYear, $this->sharedExamType($school))
            ->create();

        $schoolClass = SchoolClass::factory()
            ->forSchool($school, $academicYear->id)
            ->create();

        $schedule = ExamSchedule::factory()
            ->forPackage($package)
            ->create([
                'class_id' => $schoolClass->id,
                'subject_id' => $subject->id,
            ]);

        $participant ??= ExamParticipant::factory()
            ->forSchoolYear($school, $academicYear)
            ->create();

        return ExamAttempt::factory()->create([
            'exam_participant_id' => $participant->id,
            'exam_schedule_id' => $schedule->id,
            'exam_package_id' => $package->id,
            'status' => ExamAttemptStatus::Graded,
            'score' => $score,
            'max_score' => 100,
            'submitted_at' => now(),
        ]);
    }

    private function sharedAcademicYear(): AcademicYear
    {
        return $this->sharedAcademicYear ??= AcademicYear::factory()->active()->create();
    }

    private function sharedExamType(School $school): ExamType
    {
        return $this->sharedExamType ??= ExamType::factory()->active()->create([
            'school_id' => $school->id,
        ]);
    }
}
