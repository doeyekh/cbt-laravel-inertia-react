<?php

namespace Tests\Feature\Student;

use App\Enums\ExamPackageStatus;
use App\Enums\ExamScheduleStatus;
use App\Models\AcademicYear;
use App\Models\ClassMember;
use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\ExamParticipant;
use App\Models\ExamSchedule;
use App\Models\ExamType;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\Room;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Session;
use App\Models\Student;
use App\Models\Subject;
use App\Services\ExamAccessTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExamAttemptTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_access_exam_page_after_token_verification(): void
    {
        $context = $this->createOpenExamContext();

        // Verifikasi token terlebih dahulu
        $this->withSession($this->studentSession($context['participant']))
            ->post(route('student.exam-access.verify', $context['schedule']), [
                'token' => $context['token'],
            ])
            ->assertRedirect(route('student.exam', $context['schedule']));

        // Akses halaman pengerjaan soal
        $this->withSession($this->studentSession($context['participant']))
            ->get(route('student.exam', $context['schedule']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Student/ExamPage')
                ->where('exam.id', $context['schedule']->id)
                ->where('remaining_seconds', $context['schedule']->duration_minutes * 60)
            );
    }

    public function test_student_exam_page_restores_remaining_time_from_saved_attempt(): void
    {
        $context = $this->createOpenExamContext();
        $lastSavedAt = now()->subMinutes(5);

        ExamAttempt::factory()->create([
            'exam_participant_id' => $context['participant']->id,
            'exam_schedule_id' => $context['schedule']->id,
            'exam_package_id' => $context['package']->id,
            'duration_minutes' => 90,
            'time_spent_seconds' => 120,
            'started_at' => $lastSavedAt->copy()->subMinutes(5),
            'created_at' => $lastSavedAt->copy()->subMinutes(5),
            'updated_at' => $lastSavedAt,
        ]);

        $this->withSession($this->studentSession($context['participant']))
            ->post(route('student.exam-access.verify', $context['schedule']), [
                'token' => $context['token'],
            ])
            ->assertRedirect(route('student.exam', $context['schedule']));

        $this->withSession($this->studentSession($context['participant']))
            ->get(route('student.exam', $context['schedule']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Student/ExamPage')
                ->where('remaining_seconds', 4980)
            );
    }

    public function test_student_cannot_access_exam_page_without_token_verification(): void
    {
        $context = $this->createOpenExamContext();

        $this->withSession($this->studentSession($context['participant']))
            ->get(route('student.exam', $context['schedule']))
            ->assertRedirect(route('student.exam-access.create', $context['schedule']));
    }

    public function test_student_can_save_exam_answers(): void
    {
        $context = $this->createOpenExamContext();
        $examDate = $context['schedule']->exam_date->toDateString();
        $context['schedule']->update([
            'open_time' => '07:00:00',
            'close_time' => '11:30:00',
        ]);
        $this->travelTo(Carbon::parse("{$examDate} 03:00:00", 'UTC'));

        // Verifikasi token
        $this->withSession($this->studentSession($context['participant']))
            ->post(route('student.exam-access.verify', $context['schedule']), [
                'token' => $context['token'],
            ])
            ->assertRedirect(route('student.exam', $context['schedule']));

        // Simpan jawaban
        $question = $context['questionBank']->questions()->first();
        $multipleAnswerQuestion = $context['questionBank']->questions()
            ->orderBy('number')
            ->get()
            ->get(1);
        $multipleAnswerQuestion->update(['type' => 'pilihan_ganda_kompleks']);

        $this->withSession($this->studentSession($context['participant']))
            ->post(route('student.exam-answers.store', $context['schedule']), [
                'answers' => [
                    $question->id => 'A',
                    $multipleAnswerQuestion->id => ['B', 'C'],
                ],
                'time_spent_seconds' => 120,
            ])
            ->assertOk()
            ->assertJson([
                'message' => 'Jawaban berhasil disimpan.',
            ]);

        $this->assertDatabaseHas('exam_attempts', [
            'exam_participant_id' => $context['participant']->id,
            'exam_schedule_id' => $context['schedule']->id,
        ]);

        $this->assertDatabaseHas('exam_answers', [
            'question_id' => $question->id,
            'answer' => 'A',
        ]);

        $this->assertDatabaseHas('exam_answers', [
            'question_id' => $multipleAnswerQuestion->id,
            'answer' => '__array__:["B","C"]',
        ]);

        $this->withSession($this->studentSession($context['participant']))
            ->get(route('student.exam', $context['schedule']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Student/ExamPage')
                ->where("answers.{$question->id}", 'A')
                ->where("answers.{$multipleAnswerQuestion->id}", ['B', 'C'])
            );
    }

    public function test_student_cannot_save_answers_without_token_verification(): void
    {
        $context = $this->createOpenExamContext();

        $this->withSession($this->studentSession($context['participant']))
            ->post(route('student.exam-answers.store', $context['schedule']), [
                'answers' => [],
                'time_spent_seconds' => 0,
            ])
            ->assertStatus(403);
    }

    public function test_student_cannot_save_answers_outside_schedule_window(): void
    {
        $context = $this->createOpenExamContext();

        // Verifikasi token saat jadwal masih terbuka
        $this->withSession($this->studentSession($context['participant']))
            ->post(route('student.exam-access.verify', $context['schedule']), [
                'token' => $context['token'],
            ])
            ->assertRedirect(route('student.exam', $context['schedule']));

        // Tutup jadwal ujian
        $context['schedule']->update([
            'close_time' => '11:59:59',
        ]);

        // Mencoba menyimpan jawaban setelah jadwal ditutup harus ditolak
        $this->withSession($this->studentSession($context['participant']))
            ->post(route('student.exam-answers.store', $context['schedule']), [
                'answers' => [],
                'time_spent_seconds' => 0,
            ])
            ->assertStatus(403);
    }

    /**
     * @return array{
     *     participant: ExamParticipant,
     *     package: ExamPackage,
     *     schedule: ExamSchedule,
     *     school: School,
     *     schoolClass: SchoolClass,
     *     academicYear: AcademicYear,
     *     questionBank: QuestionBank,
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

        $questionBank = QuestionBank::factory()->create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'exam_type_id' => $examType->id,
            'subject_id' => $schedule->subject_id,
            'pilihan_ganda_count' => 10,
            'pilihan_ganda_kompleks_count' => 0,
            'menjodohkan_count' => 0,
            'isian_count' => 0,
            'esai_count' => 0,
        ]);

        Question::factory()->count(5)->sequence(
            fn ($sequence) => ['number' => $sequence->index + 1, 'type' => 'pilihan_ganda'],
        )->create([
            'question_bank_id' => $questionBank->id,
            'school_id' => $school->id,
            'subject_id' => $schedule->subject_id,
            'exam_type_id' => $examType->id,
            'class_id' => $schoolClass->id,
            'is_active' => true,
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
            'questionBank' => $questionBank,
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
