<?php

namespace Tests\Feature\Student;

use App\Enums\ExamAttemptStatus;
use App\Enums\ExamPackageStatus;
use App\Enums\ExamScheduleStatus;
use App\Models\AcademicYear;
use App\Models\ClassMember;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\ExamParticipant;
use App\Models\ExamSchedule;
use App\Models\ExamType;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionBlank;
use App\Models\QuestionOption;
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
        $startedAt = now()->subMinutes(10);

        ExamAttempt::factory()->create([
            'exam_participant_id' => $context['participant']->id,
            'exam_schedule_id' => $context['schedule']->id,
            'exam_package_id' => $context['package']->id,
            'duration_minutes' => 90,
            'time_spent_seconds' => 600,
            'started_at' => $startedAt,
            'deadline_at' => $startedAt->copy()->addMinutes(90),
            'created_at' => $startedAt,
            'updated_at' => $startedAt,
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
                ->where('remaining_seconds', 4800)
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

    public function test_exam_page_does_not_expose_answer_keys_to_the_student(): void
    {
        $context = $this->createOpenExamContext();
        $question = $context['questionBank']->questions()->orderBy('number')->firstOrFail();

        QuestionOption::factory()->for($question)->create([
            'label' => 'A',
            'text' => 'Opsi benar',
            'is_correct' => true,
            'order' => 1,
        ]);

        QuestionOption::factory()->for($question)->create([
            'label' => 'B',
            'text' => 'Opsi salah',
            'is_correct' => false,
            'order' => 2,
        ]);

        QuestionBlank::factory()->for($question)->create([
            'answers' => ['jawaban-rahasia'],
            'order' => 1,
        ]);

        $this->withSession($this->studentSession($context['participant']))
            ->post(route('student.exam-access.verify', $context['schedule']), [
                'token' => $context['token'],
            ])
            ->assertRedirect(route('student.exam', $context['schedule']));

        $this->withSession($this->studentSession($context['participant']))
            ->get(route('student.exam', $context['schedule']))
            ->assertOk()
            ->assertInertia(function (Assert $page) use ($question): void {
                $page->component('Student/ExamPage');

                $payload = collect($page->toArray()['props']['questions'])
                    ->firstWhere('id', $question->id);

                $this->assertIsArray($payload);
                $this->assertSame(
                    ['id', 'label', 'text', 'order', 'image_path', 'audio_path'],
                    array_keys($payload['options'][0]),
                );
                $this->assertSame(['id', 'order'], array_keys($payload['blanks'][0]));
                $this->assertStringNotContainsString('is_correct', json_encode($payload));
                $this->assertStringNotContainsString('jawaban-rahasia', json_encode($payload));
            });
    }

    public function test_student_cannot_save_answers_after_the_server_deadline(): void
    {
        $context = $this->createOpenExamContext();
        $question = $context['questionBank']->questions()->orderBy('number')->firstOrFail();

        $this->withSession($this->studentSession($context['participant']))
            ->post(route('student.exam-access.verify', $context['schedule']), [
                'token' => $context['token'],
            ])
            ->assertRedirect(route('student.exam', $context['schedule']));

        $this->withSession($this->studentSession($context['participant']))
            ->post(route('student.exam-answers.store', $context['schedule']), [
                'answers' => [$question->id => 'A'],
            ])
            ->assertOk();

        $attempt = ExamAttempt::query()->firstOrFail();
        $this->assertNotNull($attempt->deadline_at);
        $this->assertSame(90, $attempt->duration_minutes);

        $this->travelTo(now()->addMinutes(91));

        $this->withSession($this->studentSession($context['participant']))
            ->post(route('student.exam-answers.store', $context['schedule']), [
                'answers' => [$question->id => 'B'],
            ])
            ->assertStatus(409)
            ->assertJson([
                'status' => 'expired',
                'remaining_seconds' => 0,
            ]);

        $this->assertDatabaseHas('exam_answers', [
            'question_id' => $question->id,
            'answer' => 'A',
        ]);

        $this->assertDatabaseMissing('exam_answers', [
            'question_id' => $question->id,
            'answer' => 'B',
        ]);
    }

    public function test_submitted_attempt_cannot_accept_new_answers(): void
    {
        $context = $this->createOpenExamContext();
        $question = $context['questionBank']->questions()->orderBy('number')->firstOrFail();

        $this->withSession($this->studentSession($context['participant']))
            ->post(route('student.exam-access.verify', $context['schedule']), [
                'token' => $context['token'],
            ])
            ->assertRedirect(route('student.exam', $context['schedule']));

        $this->withSession($this->studentSession($context['participant']))
            ->post(route('student.exam-submit.store', $context['schedule']), [
                'answers' => [$question->id => 'A'],
            ])
            ->assertOk();

        $attempt = ExamAttempt::query()->firstOrFail();

        $this->assertSame(ExamAttemptStatus::Submitted, $attempt->status);
        $this->assertNotNull($attempt->submitted_at);

        $this->withSession($this->studentSession($context['participant']))
            ->post(route('student.exam-answers.store', $context['schedule']), [
                'answers' => [$question->id => 'B'],
            ])
            ->assertStatus(409)
            ->assertJson([
                'status' => 'submitted',
            ]);

        $this->assertDatabaseHas('exam_answers', [
            'exam_attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'answer' => 'A',
        ]);
        $this->assertDatabaseMissing('exam_answers', [
            'exam_attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'answer' => 'B',
        ]);
    }

    public function test_submit_after_deadline_is_finalized_and_saves_the_last_answer(): void
    {
        $context = $this->createOpenExamContext();
        $question = $context['questionBank']->questions()->orderBy('number')->firstOrFail();

        $this->withSession($this->studentSession($context['participant']))
            ->post(route('student.exam-access.verify', $context['schedule']), [
                'token' => $context['token'],
            ])
            ->assertRedirect(route('student.exam', $context['schedule']));

        $attempt = ExamAttempt::query()->createOrFirst(
            [
                'exam_participant_id' => $context['participant']->id,
                'exam_schedule_id' => $context['schedule']->id,
            ],
            [
                'exam_package_id' => $context['package']->id,
                'status' => ExamAttemptStatus::InProgress,
                'duration_minutes' => 90,
                'time_spent_seconds' => 0,
                'started_at' => now()->subMinutes(91),
                'deadline_at' => now()->subMinutes(1),
            ]
        );

        $this->withSession($this->studentSession($context['participant']))
            ->post(route('student.exam-submit.store', $context['schedule']), [
                'answers' => [$question->id => 'A'],
            ])
            ->assertOk()
            ->assertJson([
                'status' => 'submitted',
            ]);

        $attempt->refresh();

        $this->assertSame(ExamAttemptStatus::Submitted, $attempt->status);
        $this->assertNotNull($attempt->submitted_at);
        $this->assertDatabaseHas('exam_answers', [
            'exam_attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'answer' => 'A',
        ]);
    }

    public function test_flag_keeps_answer_and_clearing_answer_deletes_the_answer_row(): void
    {
        $context = $this->createOpenExamContext();
        $question = $context['questionBank']->questions()->orderBy('number')->firstOrFail();

        $this->withSession($this->studentSession($context['participant']))
            ->post(route('student.exam-access.verify', $context['schedule']), [
                'token' => $context['token'],
            ])
            ->assertRedirect(route('student.exam', $context['schedule']));

        $this->withSession($this->studentSession($context['participant']))
            ->post(route('student.exam-answers.store', $context['schedule']), [
                'answers' => [$question->id => 'A'],
                'flags' => [$question->id],
            ])
            ->assertOk();

        $examAnswer = ExamAnswer::query()
            ->where('question_id', $question->id)
            ->firstOrFail();

        $this->assertSame('A', $examAnswer->answer);
        $this->assertTrue($examAnswer->is_flagged);

        $this->withSession($this->studentSession($context['participant']))
            ->post(route('student.exam-answers.store', $context['schedule']), [
                'answers' => [$question->id => null],
                'flags' => [$question->id],
            ])
            ->assertOk();

        $this->assertDatabaseHas('exam_answers', [
            'exam_attempt_id' => $examAnswer->exam_attempt_id,
            'question_id' => $question->id,
            'answer' => null,
            'is_flagged' => true,
        ]);
    }

    public function test_student_cannot_open_an_exam_schedule_assigned_to_another_class(): void
    {
        $context = $this->createOpenExamContext();

        $otherClass = SchoolClass::factory()
            ->forSchool($context['school'], $context['academicYear']->id)
            ->active()
            ->create();
        $otherSchedule = ExamSchedule::factory()
            ->forPackage($context['package'])
            ->create([
                'class_id' => $otherClass->id,
                'subject_id' => $context['schedule']->subject_id,
                'session_id' => $context['schedule']->session_id,
                'room_id' => $context['schedule']->room_id,
                'exam_date' => now()->toDateString(),
                'open_time' => '00:00:00',
                'close_time' => '23:59:59',
                'status' => ExamScheduleStatus::Aktif,
            ]);

        $this->withSession($this->studentSession($context['participant']))
            ->post(route('student.exam-access.verify', $context['schedule']), [
                'token' => $context['token'],
            ])
            ->assertRedirect(route('student.exam', $context['schedule']));

        $this->withSession($this->studentSession($context['participant']))
            ->get(route('student.exam', $otherSchedule))
            ->assertNotFound();
    }

    public function test_elapsed_time_is_calculated_by_the_server(): void
    {
        $context = $this->createOpenExamContext();
        $question = $context['questionBank']->questions()->orderBy('number')->firstOrFail();

        $this->withSession($this->studentSession($context['participant']))
            ->post(route('student.exam-access.verify', $context['schedule']), [
                'token' => $context['token'],
            ])
            ->assertRedirect(route('student.exam', $context['schedule']));

        $this->withSession($this->studentSession($context['participant']))
            ->post(route('student.exam-answers.store', $context['schedule']), [
                'answers' => [$question->id => 'A'],
            ])
            ->assertOk();

        $this->travelTo(now()->addMinutes(5));

        $this->withSession($this->studentSession($context['participant']))
            ->post(route('student.exam-answers.store', $context['schedule']), [
                'answers' => [$question->id => 'B'],
            ])
            ->assertOk()
            ->assertJson([
                'status' => 'in_progress',
                'remaining_seconds' => 85 * 60,
            ]);

        $this->assertSame(300, ExamAttempt::query()->firstOrFail()->time_spent_seconds);
        $attempt = ExamAttempt::query()->firstOrFail();
        $this->assertNull($attempt->submitted_at);
        $this->assertSame(ExamAttemptStatus::InProgress, $attempt->status);
    }

    public function test_legacy_attempt_without_deadline_uses_creation_time_as_fallback(): void
    {
        $context = $this->createOpenExamContext();
        $createdAt = now()->subMinutes(10);

        ExamAttempt::factory()->create([
            'exam_participant_id' => $context['participant']->id,
            'exam_schedule_id' => $context['schedule']->id,
            'exam_package_id' => $context['package']->id,
            'duration_minutes' => 90,
            'deadline_at' => null,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
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
                ->where('remaining_seconds', 4800)
            );
    }

    public function test_exam_page_exposes_the_server_deadline_for_the_countdown(): void
    {
        $context = $this->createOpenExamContext();
        $startedAt = now();

        ExamAttempt::factory()->create([
            'exam_participant_id' => $context['participant']->id,
            'exam_schedule_id' => $context['schedule']->id,
            'exam_package_id' => $context['package']->id,
            'duration_minutes' => 90,
            'started_at' => $startedAt,
            'deadline_at' => $startedAt->copy()->addMinutes(90),
            'created_at' => $startedAt,
            'updated_at' => $startedAt,
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
                ->where('duration_seconds', 5400)
                ->where('remaining_seconds', 5400)
                ->where('deadline_at', $startedAt->copy()->addMinutes(90)->toIso8601String())
                ->where('server_now', $startedAt->toIso8601String())
            );
    }

    public function test_remaining_time_is_capped_at_the_configured_duration(): void
    {
        $context = $this->createOpenExamContext();
        $startedAt = now();

        $attempt = ExamAttempt::factory()->create([
            'exam_participant_id' => $context['participant']->id,
            'exam_schedule_id' => $context['schedule']->id,
            'exam_package_id' => $context['package']->id,
            'duration_minutes' => 90,
            'started_at' => $startedAt,
            'deadline_at' => $startedAt->copy()->addHours(5),
            'created_at' => $startedAt,
            'updated_at' => $startedAt,
        ]);

        $this->assertSame(5400, $attempt->remainingSeconds());
        $this->assertSame(0, $attempt->elapsedSeconds());
    }

    public function test_countdown_uses_the_attempt_duration_when_the_schedule_changes(): void
    {
        $context = $this->createOpenExamContext();
        $startedAt = now()->subMinutes(10);

        ExamAttempt::factory()->create([
            'exam_participant_id' => $context['participant']->id,
            'exam_schedule_id' => $context['schedule']->id,
            'exam_package_id' => $context['package']->id,
            'duration_minutes' => 90,
            'started_at' => $startedAt,
            'deadline_at' => $startedAt->copy()->addMinutes(90),
            'created_at' => $startedAt,
            'updated_at' => $startedAt,
        ]);

        $context['schedule']->update(['duration_minutes' => 120]);

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
                ->where('duration_seconds', 5400)
                ->where('remaining_seconds', 4800)
            );
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
