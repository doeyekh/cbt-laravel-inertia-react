<?php

namespace Tests\Feature\Admin;

use App\Enums\ExamAttemptStatus;
use App\Models\AcademicYear;
use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\ExamParticipant;
use App\Models\ExamSchedule;
use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LegerGradeControllerTest extends TestCase
{
    use RefreshDatabase;

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
                ->where('summary.student_count', 1)
                ->where('summary.average_percentage', 90));
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
            ?? AcademicYear::factory()->active()->create();

        $package = ExamPackage::factory()
            ->forSchool($school, $academicYear)
            ->create();

        $schedule = ExamSchedule::factory()
            ->forPackage($package)
            ->create([
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
}
