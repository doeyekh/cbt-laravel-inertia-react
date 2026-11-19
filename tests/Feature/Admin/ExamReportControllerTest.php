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
use App\Models\Student;
use App\Models\Subject;
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
