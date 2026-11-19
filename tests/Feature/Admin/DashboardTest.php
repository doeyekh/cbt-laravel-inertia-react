<?php

namespace Tests\Feature\Admin;

use App\Enums\ExamAttemptStatus;
use App\Enums\ExamPackageStatus;
use App\Enums\ExamScheduleStatus;
use App\Models\AcademicYear;
use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\ExamParticipant;
use App\Models\ExamSchedule;
use App\Models\ExamType;
use App\Models\Room;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_school_admin_sees_live_dashboard_metrics_for_accessible_schools(): void
    {
        $this->travelTo(now()->startOfDay()->addHours(8));

        $schoolAdmin = User::factory()->guru()->create();
        $school = School::factory()->create(['admin_id' => $schoolAdmin->id]);
        $otherSchool = School::factory()->create();
        $academicYear = AcademicYear::factory()->active()->create();
        $subject = Subject::factory()->active()->create();
        $schoolClass = SchoolClass::factory()->create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
        ]);
        $ownRoom = Room::factory()->forSchool($school)->create(['is_active' => true]);
        Room::factory()->forSchool($otherSchool)->create(['is_active' => true]);

        $ownParticipant = ExamParticipant::factory()->forSchoolYear(
            $school,
            $academicYear,
            Student::factory()->create(),
        )->create();
        ExamParticipant::factory()->forSchoolYear(
            $otherSchool,
            $academicYear,
            Student::factory()->create(),
        )->create();

        $ownPackage = ExamPackage::factory()->create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'exam_type_id' => ExamType::factory()->create(['school_id' => $school->id])->id,
            'status' => ExamPackageStatus::Aktif,
        ]);
        $ownSchedule = ExamSchedule::factory()->create([
            'exam_package_id' => $ownPackage->id,
            'class_id' => $schoolClass->id,
            'subject_id' => $subject->id,
            'exam_date' => now()->toDateString(),
            'open_time' => now()->subMinutes(30)->format('H:i:s'),
            'close_time' => now()->addMinutes(30)->format('H:i:s'),
            'status' => ExamScheduleStatus::Aktif,
            'room_id' => $ownRoom->id,
        ]);
        ExamAttempt::factory()->create([
            'exam_participant_id' => $ownParticipant->id,
            'exam_schedule_id' => $ownSchedule->id,
            'exam_package_id' => $ownPackage->id,
            'status' => ExamAttemptStatus::InProgress,
        ]);

        $otherPackage = ExamPackage::factory()->create([
            'school_id' => $otherSchool->id,
            'academic_year_id' => $academicYear->id,
            'exam_type_id' => ExamType::factory()->create(['school_id' => $otherSchool->id])->id,
            'status' => ExamPackageStatus::Aktif,
        ]);
        ExamSchedule::factory()->create([
            'exam_package_id' => $otherPackage->id,
            'class_id' => SchoolClass::factory()->create([
                'school_id' => $otherSchool->id,
                'academic_year_id' => $academicYear->id,
            ])->id,
            'subject_id' => $subject->id,
            'exam_date' => now()->toDateString(),
            'open_time' => now()->subMinutes(30)->format('H:i:s'),
            'close_time' => now()->addMinutes(30)->format('H:i:s'),
            'status' => ExamScheduleStatus::Aktif,
        ]);

        $this->actingAs($schoolAdmin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Home')
                ->where('dashboardStats.participant_count', 1)
                ->where('dashboardStats.active_room_count', 1)
                ->where('dashboardStats.today_schedule_count', 1)
                ->where('dashboardStats.active_attempt_count', 1)
                ->has('upcomingSchedules', 1)
                ->where('upcomingSchedules.0.id', $ownSchedule->id)
                ->where('upcomingSchedules.0.is_ongoing', true)
            );
    }
}
