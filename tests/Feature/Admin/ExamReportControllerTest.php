<?php

namespace Tests\Feature\Admin;

use App\Enums\ExamAttemptStatus;
use App\Enums\QuestionType;
use App\Models\AcademicYear;
use App\Models\ClassMember;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\ExamParticipant;
use App\Models\ExamSchedule;
use App\Models\ExamType;
use App\Models\Question;
use App\Models\QuestionBlank;
use App\Models\QuestionOption;
use App\Models\QuestionPair;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectTeacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExamReportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get(route('admin.reports.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_school_admin_can_view_exam_reports(): void
    {
        $schoolAdmin = User::factory()->guru()->create();
        $school = School::factory()->create(['admin_id' => $schoolAdmin->id]);
        $academicYear = AcademicYear::factory()->active()->create();
        $examType = ExamType::factory()->create(['school_id' => $school->id]);
        $subject = Subject::factory()->active()->create();
        $schoolClass = SchoolClass::factory()->create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
        ]);
        $package = ExamPackage::factory()->create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'exam_type_id' => $examType->id,
            'name' => 'Laporan Ulangan',
        ]);
        $schedule = ExamSchedule::factory()->create([
            'exam_package_id' => $package->id,
            'class_id' => $schoolClass->id,
            'subject_id' => $subject->id,
        ]);

        $this->actingAs($schoolAdmin)
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports/Index')
                ->where('title', 'Laporan Ujian')
                ->has('reports', 1)
                ->where('reports.0.id', $schedule->id)
                ->where('reports.0.name', 'Laporan Ulangan')
            );
    }

    public function test_exam_reports_can_be_filtered_by_exam_package(): void
    {
        $schoolAdmin = User::factory()->guru()->create();
        $school = School::factory()->create(['admin_id' => $schoolAdmin->id]);
        $academicYear = AcademicYear::factory()->active()->create();
        $examType = ExamType::factory()->create(['school_id' => $school->id]);
        $subject = Subject::factory()->active()->create();
        $schoolClass = SchoolClass::factory()->create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
        ]);
        $selectedPackage = ExamPackage::factory()->create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'exam_type_id' => $examType->id,
            'name' => 'Paket Terpilih',
        ]);
        $otherPackage = ExamPackage::factory()->create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'exam_type_id' => $examType->id,
            'name' => 'Paket Lain',
        ]);
        $selectedSchedule = ExamSchedule::factory()->create([
            'exam_package_id' => $selectedPackage->id,
            'class_id' => $schoolClass->id,
            'subject_id' => $subject->id,
        ]);
        ExamSchedule::factory()->create([
            'exam_package_id' => $otherPackage->id,
            'class_id' => $schoolClass->id,
            'subject_id' => $subject->id,
        ]);

        $this->actingAs($schoolAdmin)
            ->get(route('admin.reports.index', ['package_id' => $selectedPackage->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports/Index')
                ->where('filters.package_id', (string) $selectedPackage->id)
                ->has('reports', 1)
                ->where('reports.0.id', $selectedSchedule->id)
                ->where('reports.0.name', 'Paket Terpilih')
                ->has('packages', 2)
            );
    }

    public function test_school_admin_can_view_schedule_detail_and_export_excel(): void
    {
        $schoolAdmin = User::factory()->guru()->create();
        $school = School::factory()->create(['admin_id' => $schoolAdmin->id]);
        $academicYear = AcademicYear::factory()->active()->create();
        $examType = ExamType::factory()->create(['school_id' => $school->id]);
        $subject = Subject::factory()->active()->create(['name' => 'Matematika']);
        $schoolClass = SchoolClass::factory()->create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'name' => 'X IPA 1',
        ]);
        $package = ExamPackage::factory()->create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'exam_type_id' => $examType->id,
        ]);
        $schedule = ExamSchedule::factory()->create([
            'exam_package_id' => $package->id,
            'class_id' => $schoolClass->id,
            'subject_id' => $subject->id,
        ]);
        $student = Student::factory()->create(['name' => 'Siswa Contoh', 'nisn' => '1234567890']);
        $participant = ExamParticipant::factory()->create([
            'student_id' => $student->id,
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'participant_number' => 'P-001',
        ]);
        $attempt = ExamAttempt::factory()->create([
            'exam_participant_id' => $participant->id,
            'exam_schedule_id' => $schedule->id,
            'exam_package_id' => $package->id,
            'status' => ExamAttemptStatus::Submitted,
            'score' => 80,
            'max_score' => 100,
            'submitted_at' => now(),
            'time_spent_seconds' => 120,
        ]);

        $this->actingAs($schoolAdmin)
            ->get(route('admin.reports.show', $schedule))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports/Show')
                ->where('summary.participant_count', 1)
                ->where('summary.completed_count', 1)
                ->where('summary.average_percentage', 80)
                ->has('participants', 1)
                ->where('participants.0.attempt_id', $attempt->id)
                ->where('participants.0.name', 'Siswa Contoh')
                ->where('participants.0.percentage', 80)
            );

        $this->actingAs($schoolAdmin)
            ->get(route('admin.reports.export', $schedule))
            ->assertOk()
            ->assertDownload('laporan-ujian-matematika-x-ipa-1-'.$schedule->id.'.xlsx')
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_school_admin_can_review_participant_answers(): void
    {
        $schoolAdmin = User::factory()->guru()->create();
        $school = School::factory()->create(['admin_id' => $schoolAdmin->id]);
        $academicYear = AcademicYear::factory()->active()->create();
        $examType = ExamType::factory()->create(['school_id' => $school->id]);
        $subject = Subject::factory()->active()->create(['name' => 'Matematika']);
        $schoolClass = SchoolClass::factory()->create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
        ]);
        $package = ExamPackage::factory()->create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'exam_type_id' => $examType->id,
        ]);
        $schedule = ExamSchedule::factory()->create([
            'exam_package_id' => $package->id,
            'class_id' => $schoolClass->id,
            'subject_id' => $subject->id,
        ]);
        $student = Student::factory()->create(['name' => 'Siswa Contoh', 'nisn' => '1234567890']);
        $participant = ExamParticipant::factory()->create([
            'student_id' => $student->id,
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'participant_number' => 'P-001',
        ]);
        $attempt = ExamAttempt::factory()->create([
            'exam_participant_id' => $participant->id,
            'exam_schedule_id' => $schedule->id,
            'exam_package_id' => $package->id,
            'status' => ExamAttemptStatus::Graded,
            'score' => 50,
            'max_score' => 100,
            'submitted_at' => now(),
        ]);

        $optionQuestion = Question::factory()->create([
            'type' => QuestionType::PilihanGanda,
            'number' => 1,
            'body' => 'Ibu kota Indonesia adalah ...',
            'score' => 50,
        ]);
        $correctOption = QuestionOption::factory()->create([
            'question_id' => $optionQuestion->id,
            'label' => 'A',
            'text' => 'Jakarta',
            'is_correct' => true,
            'order' => 1,
        ]);
        QuestionOption::factory()->create([
            'question_id' => $optionQuestion->id,
            'label' => 'B',
            'text' => 'Bandung',
            'is_correct' => false,
            'order' => 2,
        ]);

        ExamAnswer::factory()->create([
            'exam_attempt_id' => $attempt->id,
            'question_id' => $optionQuestion->id,
            'question_no' => 1,
            'answer' => (string) $correctOption->id,
            'is_correct' => true,
            'score_awarded' => 50,
        ]);

        $essayQuestion = Question::factory()->create([
            'type' => QuestionType::Esai,
            'number' => 2,
            'body' => 'Jelaskan proses fotosintesis.',
            'score' => 50,
        ]);

        ExamAnswer::factory()->create([
            'exam_attempt_id' => $attempt->id,
            'question_id' => $essayQuestion->id,
            'question_no' => 2,
            'answer' => 'Tumbuhan menyerap cahaya matahari.',
            'is_correct' => null,
            'score_awarded' => null,
        ]);

        $matchingQuestion = Question::factory()->create([
            'type' => QuestionType::Menjodohkan,
            'number' => 3,
            'body' => 'Jodohkan istilah berikut.',
            'score' => 20,
        ]);
        QuestionPair::factory()->create([
            'question_id' => $matchingQuestion->id,
            'left_text' => 'Satu',
            'right_text' => 'One',
            'order' => 1,
        ]);
        $secondPair = QuestionPair::factory()->create([
            'question_id' => $matchingQuestion->id,
            'left_text' => 'Dua',
            'right_text' => 'Two',
            'order' => 2,
        ]);

        ExamAnswer::factory()->create([
            'exam_attempt_id' => $attempt->id,
            'question_id' => $matchingQuestion->id,
            'question_no' => 3,
            'answer' => ExamAnswer::serializeAnswer([
                (string) $secondPair->id,
                (string) $secondPair->id,
            ]),
            'is_correct' => false,
            'score_awarded' => 10,
        ]);

        $blankQuestion = Question::factory()->create([
            'type' => QuestionType::IsianSingkat,
            'number' => 4,
            'body' => 'Ibu kota Indonesia adalah ...',
            'score' => 20,
        ]);
        QuestionBlank::factory()->create([
            'question_id' => $blankQuestion->id,
            'answers' => ['Jakarta', 'DKI Jakarta'],
            'order' => 1,
        ]);

        ExamAnswer::factory()->create([
            'exam_attempt_id' => $attempt->id,
            'question_id' => $blankQuestion->id,
            'question_no' => 4,
            'answer' => 'jakarta',
            'is_correct' => true,
            'score_awarded' => 20,
        ]);

        $this->actingAs($schoolAdmin)
            ->get(route('admin.reports.attempt', [$schedule, $attempt]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports/AttemptAnswers')
                ->where('attempt.student_name', 'Siswa Contoh')
                ->where('attempt.status', 'Sudah Dinilai')
                ->where('attempt.pending_essay_count', 1)
                ->where('attempt.essay_url', route('admin.exam-attempts.essays.index', $attempt))
                ->has('questions', 4)
                ->where('questions.0.type', 'pilihan_ganda')
                ->where('questions.0.is_correct', true)
                ->where('questions.0.options.0.is_selected', true)
                ->where('questions.0.options.0.is_correct', true)
                ->where('questions.0.options.1.is_selected', false)
                ->where('questions.1.type', 'esai')
                ->where('questions.1.text_answer', 'Tumbuhan menyerap cahaya matahari.')
                ->where('questions.2.type', 'menjodohkan')
                ->where('questions.2.pairs.0.correct_right', 'One')
                ->where('questions.2.pairs.0.selected_right', 'Two')
                ->where('questions.2.pairs.0.is_correct', false)
                ->where('questions.2.pairs.1.selected_right', 'Two')
                ->where('questions.2.pairs.1.is_correct', true)
                ->where('questions.3.type', 'isian_singkat')
                ->where('questions.3.blanks.0.student_answer', 'jakarta')
                ->where('questions.3.blanks.0.is_correct', true)
                ->where('questions.3.blanks.0.accepted_answers.0', 'Jakarta')
            );
    }

    public function test_school_admin_can_download_report_pdf(): void
    {
        $schoolAdmin = User::factory()->guru()->create();
        $school = School::factory()->create(['admin_id' => $schoolAdmin->id]);
        $academicYear = AcademicYear::factory()->active()->create();
        $examType = ExamType::factory()->create(['school_id' => $school->id]);
        $subject = Subject::factory()->active()->create(['name' => 'Bahasa Indonesia']);
        $schoolClass = SchoolClass::factory()->create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'name' => 'X RPL 1',
        ]);
        $package = ExamPackage::factory()->create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'exam_type_id' => $examType->id,
        ]);
        $schedule = ExamSchedule::factory()->create([
            'exam_package_id' => $package->id,
            'class_id' => $schoolClass->id,
            'subject_id' => $subject->id,
        ]);
        $student = Student::factory()->create(['name' => 'Siswa Contoh']);
        ClassMember::factory()->forClass($schoolClass, $student)->create();
        $unstartedStudent = Student::factory()->create(['name' => 'Siswa Belum Memulai']);
        ClassMember::factory()->forClass($schoolClass, $unstartedStudent)->create();
        SubjectTeacher::factory()
            ->forClass($schoolClass, $academicYear, $subject, $schoolAdmin)
            ->create(['is_active' => true]);
        $participant = ExamParticipant::factory()->create([
            'student_id' => $student->id,
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
        ]);
        ExamAttempt::factory()->create([
            'exam_participant_id' => $participant->id,
            'exam_schedule_id' => $schedule->id,
            'exam_package_id' => $package->id,
            'status' => ExamAttemptStatus::Graded,
            'score' => 80,
            'max_score' => 100,
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($schoolAdmin)
            ->get(route('admin.reports.pdf', $schedule));

        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader(
                'content-disposition',
                'inline; filename="laporan-ujian-bahasa-indonesia-x-rpl-1-'.$schedule->id.'.pdf"',
            );

        $this->assertStringStartsWith('%PDF', $response->streamedContent());
    }

    public function test_report_pdf_view_formats_scores_without_percent_and_shows_teacher_signature(): void
    {
        $html = view('reports.pdf', [
            'schedule' => [
                'name' => 'Ujian Bahasa Indonesia',
                'subject' => 'Bahasa Indonesia',
                'teacher' => 'Guru Contoh',
            ],
            'summary' => [],
            'participants' => [
                [
                    'name' => 'Siswa Belum Memulai',
                    'percentage' => null,
                    'time_spent_seconds' => null,
                ],
                [
                    'name' => 'Siswa Contoh',
                    'percentage' => 87.5,
                    'time_spent_seconds' => 120,
                ],
            ],
            'print_date' => '19/11/2026 16:00',
        ])->render();

        $this->assertStringContainsString('Siswa Belum Memulai', $html);
        $this->assertStringContainsString('Guru Contoh', $html);
        $this->assertStringContainsString('87,5', $html);
        $this->assertStringNotContainsString('87,5%', $html);
    }

    public function test_school_admin_cannot_review_answers_from_another_schedule(): void
    {
        $schoolAdmin = User::factory()->guru()->create();
        $school = School::factory()->create(['admin_id' => $schoolAdmin->id]);
        $academicYear = AcademicYear::factory()->active()->create();
        $examType = ExamType::factory()->create(['school_id' => $school->id]);
        $subject = Subject::factory()->active()->create();
        $schoolClass = SchoolClass::factory()->create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
        ]);
        $package = ExamPackage::factory()->create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'exam_type_id' => $examType->id,
        ]);
        $selectedSchedule = ExamSchedule::factory()->create([
            'exam_package_id' => $package->id,
            'class_id' => $schoolClass->id,
            'subject_id' => $subject->id,
        ]);
        $otherSchedule = ExamSchedule::factory()->create([
            'exam_package_id' => $package->id,
            'class_id' => $schoolClass->id,
            'subject_id' => Subject::factory()->active()->create()->id,
        ]);
        $attempt = ExamAttempt::factory()->create([
            'exam_schedule_id' => $otherSchedule->id,
            'exam_package_id' => $package->id,
        ]);

        $this->actingAs($schoolAdmin)
            ->get(route('admin.reports.attempt', [$selectedSchedule, $attempt]))
            ->assertNotFound();
    }

    public function test_school_admin_cannot_view_report_from_another_school(): void
    {
        $schoolAdmin = User::factory()->guru()->create();
        School::factory()->create(['admin_id' => $schoolAdmin->id]);
        $schedule = ExamSchedule::factory()->create();

        $this->actingAs($schoolAdmin)
            ->get(route('admin.reports.show', $schedule))
            ->assertNotFound();
    }

    public function test_platform_administrator_cannot_view_reports(): void
    {
        $this->actingAs(User::factory()->administrator()->create())
            ->get(route('admin.reports.index'))
            ->assertForbidden();
    }
}
