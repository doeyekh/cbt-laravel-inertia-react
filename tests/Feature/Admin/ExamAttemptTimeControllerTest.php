<?php

namespace Tests\Feature\Admin;

use App\Enums\ExamAttemptStatus;
use App\Enums\ExamPackageStatus;
use App\Enums\ExamScheduleStatus;
use App\Models\AcademicYear;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\ExamParticipant;
use App\Models\ExamSchedule;
use App\Models\ExamType;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamAttemptTimeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_extend_time_for_an_in_progress_attempt(): void
    {
        $context = $this->createAttemptContext();
        $this->travelTo(now()->startOfMinute());
        $startedAt = now()->subMinutes(30);
        $deadline = now()->addMinutes(60);
        $context['attempt']->update([
            'started_at' => $startedAt,
            'deadline_at' => $deadline,
            'duration_minutes' => 90,
        ]);

        $this->actingAs($context['admin'])
            ->patch(route('admin.exam-attempts.extend-time', $context['attempt']), ['minutes' => 20])
            ->assertRedirect();

        $context['attempt']->refresh();
        $this->assertSame(110, $context['attempt']->duration_minutes);
        $this->assertSame($deadline->addMinutes(20)->timestamp, $context['attempt']->deadline_at->timestamp);
        $this->assertSame(ExamAttemptStatus::InProgress, $context['attempt']->status);
    }

    public function test_extending_time_rejects_minutes_outside_the_allowed_range(): void
    {
        $context = $this->createAttemptContext();
        $originalDeadline = $context['attempt']->deadline_at;

        $this->actingAs($context['admin'])
            ->patch(route('admin.exam-attempts.extend-time', $context['attempt']), ['minutes' => 241])
            ->assertSessionHasErrors('minutes');

        $this->assertSame(
            $originalDeadline?->timestamp,
            $context['attempt']->fresh()->deadline_at?->timestamp,
        );
    }

    public function test_school_admin_can_unlock_a_graded_attempt_while_preserving_answers(): void
    {
        $context = $this->createAttemptContext(ExamAttemptStatus::Graded);
        $answer = ExamAnswer::factory()->create([
            'exam_attempt_id' => $context['attempt']->id,
            'answer' => 'jawaban dipertahankan',
            'is_flagged' => true,
            'is_correct' => true,
            'score_awarded' => 5,
        ]);
        $context['attempt']->update([
            'score' => 80,
            'max_score' => 100,
            'submitted_at' => now()->subMinutes(10),
            'graded_at' => now()->subMinutes(5),
            'graded_by' => $context['admin']->id,
            'deadline_at' => now()->subMinutes(1),
        ]);
        $this->travelTo(now()->addMinute());

        $this->actingAs($context['admin'])
            ->patch(route('admin.exam-attempts.unlock', $context['attempt']), ['minutes' => 15])
            ->assertRedirect()
            ->assertSessionHas('toast.title', 'Attempt Dibuka Kembali');

        $context['attempt']->refresh();
        $this->assertSame(ExamAttemptStatus::InProgress, $context['attempt']->status);
        $this->assertNull($context['attempt']->submitted_at);
        $this->assertNull($context['attempt']->score);
        $this->assertNull($context['attempt']->graded_at);
        $this->assertNull($context['attempt']->graded_by);
        $this->assertGreaterThan(now()->timestamp, $context['attempt']->resolveDeadline()->timestamp);

        $answer->refresh();
        $this->assertSame('jawaban dipertahankan', $answer->answer);
        $this->assertTrue($answer->is_flagged);
        $this->assertNull($answer->is_correct);
        $this->assertNull($answer->score_awarded);
    }

    public function test_school_admin_cannot_extend_or_unlock_attempt_from_another_school(): void
    {
        $context = $this->createAttemptContext();
        $otherAdmin = User::factory()->guru()->create();
        School::factory()->create(['admin_id' => $otherAdmin->id]);
        $originalDeadline = $context['attempt']->deadline_at;

        $this->actingAs($otherAdmin)
            ->patch(route('admin.exam-attempts.extend-time', $context['attempt']), ['minutes' => 15])
            ->assertNotFound();

        $this->assertSame(
            $originalDeadline?->timestamp,
            $context['attempt']->fresh()->deadline_at?->timestamp,
        );
    }

    /**
     * @return array{admin: User, attempt: ExamAttempt}
     */
    private function createAttemptContext(ExamAttemptStatus $status = ExamAttemptStatus::InProgress): array
    {
        $admin = User::factory()->guru()->create();
        $school = School::factory()->create(['admin_id' => $admin->id]);
        $academicYear = AcademicYear::factory()->active()->create();
        $examType = ExamType::factory()->create(['school_id' => $school->id]);
        $schoolClass = SchoolClass::factory()->create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
        ]);
        $package = ExamPackage::factory()->create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'exam_type_id' => $examType->id,
            'status' => ExamPackageStatus::Aktif,
        ]);
        $schedule = ExamSchedule::factory()->create([
            'exam_package_id' => $package->id,
            'class_id' => $schoolClass->id,
            'subject_id' => Subject::factory()->active()->create()->id,
            'status' => ExamScheduleStatus::Aktif,
        ]);
        $participant = ExamParticipant::factory()->forSchoolYear(
            $school,
            $academicYear,
            Student::factory()->create(),
        )->create();
        $attempt = ExamAttempt::factory()->create([
            'exam_participant_id' => $participant->id,
            'exam_schedule_id' => $schedule->id,
            'exam_package_id' => $package->id,
            'status' => $status,
            'started_at' => now()->subMinutes(30),
            'deadline_at' => now()->addMinutes(60),
            'duration_minutes' => 90,
        ]);

        return [
            'admin' => $admin,
            'attempt' => $attempt,
        ];
    }
}
