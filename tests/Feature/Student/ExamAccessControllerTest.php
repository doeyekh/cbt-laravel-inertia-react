<?php

namespace Tests\Feature\Student;

use App\Enums\ExamPackageStatus;
use App\Enums\ExamScheduleStatus;
use App\Models\AcademicYear;
use App\Models\ClassMember;
use App\Models\ExamPackage;
use App\Models\ExamParticipant;
use App\Models\ExamSchedule;
use App\Models\ExamType;
use App\Models\Room;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Session;
use App\Models\Student;
use App\Models\Subject;
use App\Services\ExamAccessTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExamAccessControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_gets_package_access_after_submitting_the_valid_token(): void
    {
        $context = $this->createOpenExamContext();

        $this->withSession($this->studentSession($context['participant']))
            ->post(route('student.exam-access.verify', $context['schedule']), [
                'token' => $context['token'],
            ])
            ->assertRedirect(route('student.dashboard'))
            ->assertSessionHas(
                "student_exam_packages.{$context['package']->id}",
                $context['token_hash'],
            );

        $this->get(route('student.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('exams.0.token_available', true)
                ->where('exams.0.token_access_granted', true)
            );
    }

    public function test_the_same_package_token_is_valid_for_other_sessions_and_rooms(): void
    {
        $context = $this->createOpenExamContext();
        $otherStudent = Student::factory()->create();
        $otherRoom = Room::factory()->forSchool($context['school'])->create();
        $otherSession = Session::factory()->forSchool($context['school'])->create();
        $otherSchedule = ExamSchedule::factory()
            ->forPackage($context['package'])
            ->create([
                'class_id' => $context['schoolClass']->id,
                'subject_id' => Subject::factory()->create()->id,
                'session_id' => $otherSession->id,
                'room_id' => $otherRoom->id,
                'exam_date' => now()->toDateString(),
                'open_time' => '00:00:00',
                'close_time' => '23:59:59',
                'status' => ExamScheduleStatus::Aktif,
            ]);
        ClassMember::factory()->forClass($context['schoolClass'], $otherStudent)->create();
        $otherParticipant = ExamParticipant::factory()
            ->forSchoolYear($context['school'], $context['academicYear'], $otherStudent)
            ->placedIn($otherRoom->id, $otherSession->id)
            ->create();

        $this->withSession($this->studentSession($otherParticipant))
            ->post(route('student.exam-access.verify', $otherSchedule), [
                'token' => $context['token'],
            ])
            ->assertRedirect(route('student.dashboard'))
            ->assertSessionHas(
                "student_exam_packages.{$context['package']->id}",
                $context['token_hash'],
            );
    }

    public function test_rotating_the_package_token_invalidates_a_previous_student_grant(): void
    {
        $context = $this->createOpenExamContext();

        $this->withSession($this->studentSession($context['participant']))
            ->post(route('student.exam-access.verify', $context['schedule']), [
                'token' => $context['token'],
            ])
            ->assertRedirect(route('student.dashboard'));

        $context['package']->forceFill([
            'exam_access_token_hash' => hash('sha256', 'rotated-token'),
        ])->save();

        $this->get(route('student.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('exams.0.token_available', true)
                ->where('exams.0.token_access_granted', false)
            );
    }

    public function test_student_cannot_verify_the_token_outside_the_schedule_window(): void
    {
        $context = $this->createOpenExamContext();
        $context['schedule']->update([
            'close_time' => '11:59:59',
        ]);

        $this->withSession($this->studentSession($context['participant']))
            ->from(route('student.dashboard'))
            ->post(route('student.exam-access.verify', $context['schedule']), [
                'token' => $context['token'],
            ])
            ->assertRedirect(route('student.dashboard'))
            ->assertSessionHasErrors([
                'token' => 'Jadwal ujian belum dibuka, sudah ditutup, atau tidak aktif.',
            ])
            ->assertSessionMissing("student_exam_packages.{$context['package']->id}");
    }

    public function test_student_cannot_get_package_access_with_an_incorrect_token(): void
    {
        $context = $this->createOpenExamContext();

        $this->withSession($this->studentSession($context['participant']))
            ->from(route('student.dashboard'))
            ->post(route('student.exam-access.verify', $context['schedule']), [
                'token' => 'B2C4E6',
            ])
            ->assertRedirect(route('student.dashboard'))
            ->assertSessionHasErrors(['token' => 'Token tidak sesuai.'])
            ->assertSessionMissing("student_exam_packages.{$context['package']->id}");
    }

    public function test_student_cannot_verify_a_schedule_assigned_to_another_room(): void
    {
        $context = $this->createOpenExamContext();
        $otherRoom = Room::factory()->forSchool($context['school'])->create();
        $otherSession = Session::factory()->forSchool($context['school'])->create();
        $otherSchedule = ExamSchedule::factory()
            ->forPackage($context['package'])
            ->create([
                'class_id' => $context['schoolClass']->id,
                'subject_id' => Subject::factory()->create()->id,
                'session_id' => $otherSession->id,
                'room_id' => $otherRoom->id,
                'exam_date' => now()->toDateString(),
                'open_time' => '00:00:00',
                'close_time' => '23:59:59',
                'status' => ExamScheduleStatus::Aktif,
            ]);

        $this->withSession($this->studentSession($context['participant']))
            ->post(route('student.exam-access.verify', $otherSchedule), [
                'token' => $context['token'],
            ])
            ->assertNotFound()
            ->assertSessionMissing("student_exam_packages.{$context['package']->id}");
    }

    public function test_student_is_rate_limited_after_five_incorrect_tokens(): void
    {
        $context = $this->createOpenExamContext();
        $rateLimitKey = "exam-access-token:{$context['package']->id}:{$context['participant']->id}";
        RateLimiter::clear($rateLimitKey);
        $this->withSession($this->studentSession($context['participant']));

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->from(route('student.dashboard'))
                ->post(route('student.exam-access.verify', $context['schedule']), [
                    'token' => 'B2C4E6',
                ])
                ->assertSessionHasErrors('token');
        }

        $this->from(route('student.dashboard'))
            ->post(route('student.exam-access.verify', $context['schedule']), [
                'token' => $context['token'],
            ])
            ->assertSessionHasErrors([
                'token' => 'Terlalu banyak percobaan. Silakan coba kembali dalam beberapa menit.',
            ])
            ->assertSessionMissing("student_exam_packages.{$context['package']->id}");
    }

    /**
     * @return array{
     *     participant: ExamParticipant,
     *     package: ExamPackage,
     *     schedule: ExamSchedule,
     *     school: School,
     *     schoolClass: SchoolClass,
     *     academicYear: AcademicYear,
     *     token: string,
     *     token_hash: string
     * }
     */
    private function createOpenExamContext(): array
    {
        $this->travelTo(now()->setTime(12, 0));

        $school = School::factory()->create();
        $academicYear = AcademicYear::factory()->active()->create();
        $student = Student::factory()->create();
        $schoolClass = SchoolClass::factory()
            ->forSchool($school, $academicYear->id)
            ->active()
            ->create();
        $room = Room::factory()->forSchool($school)->create();
        $session = Session::factory()->forSchool($school)->create();

        ClassMember::factory()->forClass($schoolClass, $student)->create();

        $participant = ExamParticipant::factory()
            ->forSchoolYear($school, $academicYear, $student)
            ->placedIn($room->id, $session->id)
            ->create();

        $examType = ExamType::factory()->active()->create([
            'school_id' => $school->id,
        ]);
        $today = now()->toDateString();
        $package = ExamPackage::factory()
            ->forSchool($school, $academicYear, $examType)
            ->withStatus(ExamPackageStatus::Aktif)
            ->create([
                'start_date' => $today,
                'end_date' => $today,
            ]);
        $schedule = ExamSchedule::factory()
            ->forPackage($package)
            ->create([
                'class_id' => $schoolClass->id,
                'subject_id' => Subject::factory()->create()->id,
                'session_id' => $session->id,
                'room_id' => $room->id,
                'exam_date' => $today,
                'open_time' => '00:00:00',
                'close_time' => '23:59:59',
                'status' => ExamScheduleStatus::Aktif,
            ]);

        $token = 'A2C4E6';
        $tokenHash = app(ExamAccessTokenService::class)->hashToken($token);
        $package->forceFill([
            'exam_access_token_hash' => $tokenHash,
            'exam_access_token_generated_at' => now(),
        ])->save();

        return [
            'participant' => $participant,
            'package' => $package,
            'schedule' => $schedule,
            'school' => $school,
            'schoolClass' => $schoolClass,
            'academicYear' => $academicYear,
            'token' => $token,
            'token_hash' => $tokenHash,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function studentSession(ExamParticipant $participant): array
    {
        return [
            'student_participant_id' => $participant->id,
            'student_name' => 'Peserta Ujian',
        ];
    }
}
