<?php

namespace Tests\Feature\Student;

use App\Enums\ExamPackageStatus;
use App\Enums\ExamScheduleStatus;
use App\Models\AcademicYear;
use App\Models\ClassMember;
use App\Models\ExamPackage;
use App\Models\ExamParticipant;
use App\Models\ExamSchedule;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StudentDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_student_login(): void
    {
        $this->get('/siswa')->assertRedirect(route('student.login'));
    }

    public function test_student_can_see_their_identity_on_the_dashboard(): void
    {
        $context = $this->createParticipantContext();

        $this->withSession($this->studentSession($context['participant']))
            ->get('/siswa')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Student/Dashboard')
                ->where('participant.name', 'Budi Pratama')
                ->where('participant.username', 'BUDI001')
                ->where('participant.school', 'SMA Negeri 1')
                ->where('participant.class_name', 'XII IPA 1')
                ->where('participant.academic_year', '2026/2027')
                ->has('exams', 0)
            );
    }

    public function test_dashboard_lists_active_exams_for_the_student_class(): void
    {
        $context = $this->createParticipantContext();

        $examPackage = ExamPackage::factory()
            ->forSchool($context['school'], $context['academicYear'])
            ->withStatus(ExamPackageStatus::Aktif)
            ->create();

        $draftPackage = ExamPackage::factory()
            ->forSchool($context['school'], $context['academicYear'])
            ->withStatus(ExamPackageStatus::Draft)
            ->create();

        $otherClass = SchoolClass::factory()->create([
            'school_id' => $context['school']->id,
            'academic_year_id' => $context['academicYear']->id,
        ]);

        $subject = Subject::factory()->create(['name' => 'Matematika']);

        ExamSchedule::factory()->forPackage($examPackage)->create([
            'class_id' => $context['schoolClass']->id,
            'subject_id' => $subject->id,
            'exam_date' => '2026-11-20',
            'open_time' => '07:30:00',
            'close_time' => '09:00:00',
            'status' => ExamScheduleStatus::Aktif,
        ]);

        ExamSchedule::factory()->forPackage($examPackage)->create([
            'class_id' => $context['schoolClass']->id,
            'subject_id' => Subject::factory()->create(['name' => 'Bahasa Indonesia'])->id,
            'status' => ExamScheduleStatus::Nonaktif,
        ]);

        ExamSchedule::factory()->forPackage($draftPackage)->create([
            'class_id' => $context['schoolClass']->id,
            'subject_id' => Subject::factory()->create(['name' => 'Fisika'])->id,
            'status' => ExamScheduleStatus::Aktif,
        ]);

        ExamSchedule::factory()->forPackage($examPackage)->create([
            'class_id' => $otherClass->id,
            'subject_id' => Subject::factory()->create(['name' => 'Kimia'])->id,
            'status' => ExamScheduleStatus::Aktif,
        ]);

        $this->withSession($this->studentSession($context['participant']))
            ->get('/siswa')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('exams', 1)
                ->where('exams.0.subject', 'Matematika')
                ->where('exams.0.date', '2026-11-20')
                ->where('exams.0.open_time', '07:30')
                ->where('exams.0.close_time', '09:00')
            );
    }

    public function test_dashboard_clears_session_when_participant_no_longer_exists(): void
    {
        $this->withSession(['student_participant_id' => 9999])
            ->get('/siswa')
            ->assertRedirect(route('student.login'))
            ->assertSessionMissing('student_participant_id');
    }

    /**
     * Create a participant with an active school class for the current academic year.
     *
     * @return array{participant: ExamParticipant, school: School, academicYear: AcademicYear, schoolClass: SchoolClass}
     */
    private function createParticipantContext(): array
    {
        $school = School::factory()->create(['name' => 'SMA Negeri 1']);
        $academicYear = AcademicYear::factory()->ganjil()->active()->create(['name' => '2026/2027']);
        $student = Student::factory()->create(['name' => 'Budi Pratama']);

        $participant = ExamParticipant::factory()
            ->forSchoolYear($school, $academicYear, $student)
            ->create([
                'username' => 'BUDI001',
                'participant_number' => '01-001',
            ]);

        $schoolClass = SchoolClass::factory()->create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'name' => 'XII IPA 1',
        ]);

        ClassMember::factory()->create([
            'class_id' => $schoolClass->id,
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
        ]);

        return [
            'participant' => $participant,
            'school' => $school,
            'academicYear' => $academicYear,
            'schoolClass' => $schoolClass,
        ];
    }

    /**
     * Build the student session payload used by the portal middleware.
     *
     * @return array<string, mixed>
     */
    private function studentSession(ExamParticipant $participant): array
    {
        return [
            'student_participant_id' => $participant->id,
            'student_name' => 'Budi Pratama',
        ];
    }
}
