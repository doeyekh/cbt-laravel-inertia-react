<?php

namespace Tests\Feature\Admin;

use App\Enums\ExamPackageStatus;
use App\Models\AcademicYear;
use App\Models\ExamPackage;
use App\Models\ExamSchedule;
use App\Models\ExamType;
use App\Models\Room;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Session;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExamPackageControllerTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $academicYear;

    protected function setUp(): void
    {
        parent::setUp();

        $this->academicYear = AcademicYear::factory()->active()->create();
        $this->school = School::factory()->create();
        $this->actingAs(User::factory()->administrator()->create());
    }

    public function test_index_returns_paginated_exam_packages(): void
    {
        ExamPackage::factory()->count(12)->create();

        $this->get(route('admin.exam-packages.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ExamPackages/Index')
                ->has('examPackages.data', 10)
                ->where('examPackages.total', 12)
                ->has('schools')
                ->where('activeAcademicYear.id', $this->academicYear->id)
                ->has('examTypes')
                ->has('statuses')
                ->has('stats')
            );
    }

    public function test_index_scopes_packages_to_the_active_academic_year(): void
    {
        $pastYear = AcademicYear::factory()->inactive()->create();

        $activePackage = $this->packageFor($this->school);
        $pastPackage = $this->packageFor($this->school, $pastYear);

        $this->get(route('admin.exam-packages.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('examPackages.data', 1)
                ->where('examPackages.data.0.id', $activePackage->id)
                ->where('stats.total', 1)
            );

        $this->assertNotSame($activePackage->id, $pastPackage->id);
    }

    public function test_index_reports_the_exam_type_active_status(): void
    {
        $activeType = ExamType::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $inactiveType = ExamType::factory()->create(['school_id' => $this->school->id, 'is_active' => false]);

        $activePackage = $this->packageFor($this->school, examType: $activeType, attributes: ['code' => 'PKT-ACT']);
        $inactivePackage = $this->packageFor($this->school, examType: $inactiveType, attributes: ['code' => 'PKT-INA']);

        $this->get(route('admin.exam-packages.index', ['search' => 'PKT-ACT']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('examPackages.data', 1)
                ->where('examPackages.data.0.id', $activePackage->id)
                ->where('examPackages.data.0.exam_type_is_active', true)
            );

        $this->get(route('admin.exam-packages.index', ['search' => 'PKT-INA']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('examPackages.data', 1)
                ->where('examPackages.data.0.id', $inactivePackage->id)
                ->where('examPackages.data.0.exam_type_is_active', false)
            );
    }

    public function test_index_filters_by_search_school_type_and_status(): void
    {
        $examType = ExamType::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $package = $this->packageFor($this->school, $this->academicYear, $examType, [
            'code' => 'PTS-2026',
            'name' => 'PTS Ganjil',
            'status' => ExamPackageStatus::Aktif,
        ]);
        ExamPackage::factory()->create(['status' => ExamPackageStatus::Draft]);

        $this->get(route('admin.exam-packages.index', [
            'search' => 'PTS-2026',
            'school_id' => $this->school->id,
            'exam_type_id' => $examType->id,
            'status' => ExamPackageStatus::Aktif->value,
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('examPackages.data', 1)
                ->where('examPackages.data.0.id', $package->id)
                ->where('filters.school_id', $this->school->id)
                ->where('filters.exam_type_id', $examType->id)
                ->where('filters.status', ExamPackageStatus::Aktif->value)
            );
    }

    public function test_index_includes_status_stats(): void
    {
        $this->packageFor($this->school, status: ExamPackageStatus::Draft);
        $this->packageFor($this->school, status: ExamPackageStatus::Aktif);
        $this->packageFor($this->school, status: ExamPackageStatus::Aktif);
        $this->packageFor($this->school, status: ExamPackageStatus::Selesai);

        $this->get(route('admin.exam-packages.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.total', 4)
                ->where('stats.draft', 1)
                ->where('stats.active', 2)
                ->where('stats.completed', 1)
            );
    }

    public function test_index_scopes_packages_to_accessible_schools(): void
    {
        $schoolAdmin = User::factory()->guru()->create();
        $ownSchool = School::factory()->create(['admin_id' => $schoolAdmin->id]);
        $otherSchool = School::factory()->create();

        $mine = $this->packageFor($ownSchool);
        $this->packageFor($otherSchool);

        $this->actingAs($schoolAdmin)
            ->get(route('admin.exam-packages.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('examPackages.data', 1)
                ->where('examPackages.data.0.id', $mine->id)
                ->where('stats.total', 1)
            );
    }

    public function test_index_exposes_exam_type_active_status_for_the_form(): void
    {
        ExamType::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        ExamType::factory()->create(['school_id' => $this->school->id, 'is_active' => false]);

        $this->get(route('admin.exam-packages.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('examTypes', 2)
                ->where('examTypes', function ($examTypes): bool {
                    $examTypes = collect($examTypes);

                    return $examTypes->contains(fn ($examType) => $examType['is_active'] === true)
                        && $examTypes->contains(fn ($examType) => $examType['is_active'] === false);
                })
            );
    }

    public function test_index_forbids_regular_guru(): void
    {
        $this->actingAs(User::factory()->guru()->create())
            ->get(route('admin.exam-packages.index'))
            ->assertForbidden();
    }

    public function test_show_returns_package_with_schedules(): void
    {
        $package = $this->packageFor($this->school);
        $schedule = $this->scheduleFor($package);

        $this->get(route('admin.exam-packages.show', $package))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ExamPackages/Show')
                ->where('examPackage.id', $package->id)
                ->has('schedules', 1)
                ->where('schedules.0.id', $schedule->id)
                ->has('classes')
                ->has('subjectsByClass')
                ->has('sessions')
                ->has('rooms')
            );
    }

    public function test_show_reports_the_exam_type_active_status(): void
    {
        $examType = ExamType::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $package = $this->packageFor($this->school, examType: $examType);

        $this->get(route('admin.exam-packages.show', $package))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('examPackage.id', $package->id)
                ->where('examPackage.exam_type_is_active', true)
            );
    }

    public function test_store_creates_an_exam_package(): void
    {
        $examType = ExamType::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);

        $this->post(route('admin.exam-packages.store'), [
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'exam_type_id' => $examType->id,
            'code' => 'PTS-2026',
            'name' => 'PTS Ganjil 2026/2027',
            'description' => 'Ujian tengah semester.',
            'start_date' => '2026-09-14',
            'end_date' => '2026-09-19',
            'status' => ExamPackageStatus::Draft->value,
        ])->assertRedirect()->assertSessionHas('toast.type', 'success');

        $this->assertDatabaseHas('exam_packages', [
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'exam_type_id' => $examType->id,
            'code' => 'PTS-2026',
            'name' => 'PTS Ganjil 2026/2027',
            'status' => ExamPackageStatus::Draft->value,
        ]);
    }

    public function test_store_ignores_the_submitted_academic_year(): void
    {
        $otherYear = AcademicYear::factory()->inactive()->create();
        $examType = ExamType::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);

        $this->post(route('admin.exam-packages.store'), [
            'school_id' => $this->school->id,
            'academic_year_id' => $otherYear->id,
            'exam_type_id' => $examType->id,
            'code' => 'PTS-2026',
            'name' => 'PTS Ganjil',
            'start_date' => '2026-09-14',
            'end_date' => '2026-09-19',
            'status' => ExamPackageStatus::Draft->value,
        ])->assertRedirect()->assertSessionHas('toast.type', 'success');

        $this->assertSame(
            $this->academicYear->id,
            ExamPackage::query()->firstOrFail()->academic_year_id
        );
    }

    public function test_store_rejects_when_there_is_no_active_academic_year(): void
    {
        $otherYear = AcademicYear::factory()->inactive()->create();
        $examType = ExamType::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);

        $this->academicYear->forceFill(['is_active' => false])->save();

        $this->post(route('admin.exam-packages.store'), [
            'school_id' => $this->school->id,
            'academic_year_id' => $otherYear->id,
            'exam_type_id' => $examType->id,
            'code' => 'PTS-2026',
            'name' => 'PTS Ganjil',
            'start_date' => '2026-09-14',
            'end_date' => '2026-09-19',
            'status' => ExamPackageStatus::Draft->value,
        ])->assertSessionHasErrors('academic_year_id');

        $this->assertDatabaseCount('exam_packages', 0);
    }

    public function test_store_rejects_invalid_dates(): void
    {
        $academicYear = AcademicYear::factory()->create();
        $examType = ExamType::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);

        $this->post(route('admin.exam-packages.store'), [
            'school_id' => $this->school->id,
            'academic_year_id' => $academicYear->id,
            'exam_type_id' => $examType->id,
            'code' => 'PTS-2026',
            'name' => 'PTS',
            'start_date' => '2026-09-19',
            'end_date' => '2026-09-14',
            'status' => ExamPackageStatus::Draft->value,
        ])->assertSessionHasErrors('end_date');

        $this->assertDatabaseCount('exam_packages', 0);
    }

    public function test_store_rejects_duplicate_code_in_the_same_school(): void
    {
        $academicYear = AcademicYear::factory()->create();
        $examType = ExamType::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $this->packageFor($this->school, $academicYear, $examType, ['code' => 'PTS-2026']);

        $this->post(route('admin.exam-packages.store'), [
            'school_id' => $this->school->id,
            'academic_year_id' => $academicYear->id,
            'exam_type_id' => $examType->id,
            'code' => 'PTS-2026',
            'name' => 'PTS Lain',
            'start_date' => '2026-09-14',
            'end_date' => '2026-09-19',
            'status' => ExamPackageStatus::Draft->value,
        ])->assertSessionHasErrors('code');
    }

    public function test_store_allows_the_same_code_in_a_different_school(): void
    {
        $otherSchool = School::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        $otherType = ExamType::factory()->create(['school_id' => $otherSchool->id, 'is_active' => true]);
        $this->packageFor($otherSchool, $academicYear, $otherType, ['code' => 'PTS-2026']);

        $examType = ExamType::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);

        $this->post(route('admin.exam-packages.store'), [
            'school_id' => $this->school->id,
            'academic_year_id' => $academicYear->id,
            'exam_type_id' => $examType->id,
            'code' => 'PTS-2026',
            'name' => 'PTS Sekolah Sendiri',
            'start_date' => '2026-09-14',
            'end_date' => '2026-09-19',
            'status' => ExamPackageStatus::Draft->value,
        ])->assertRedirect();

        $this->assertSame(2, ExamPackage::query()->where('code', 'PTS-2026')->count());
    }

    public function test_store_rejects_exam_type_from_another_school(): void
    {
        $academicYear = AcademicYear::factory()->create();
        $otherType = ExamType::factory()->create(['school_id' => School::factory()->create()->id, 'is_active' => true]);

        $this->post(route('admin.exam-packages.store'), [
            'school_id' => $this->school->id,
            'academic_year_id' => $academicYear->id,
            'exam_type_id' => $otherType->id,
            'code' => 'PTS-2026',
            'name' => 'PTS',
            'start_date' => '2026-09-14',
            'end_date' => '2026-09-19',
            'status' => ExamPackageStatus::Draft->value,
        ])->assertSessionHasErrors('exam_type_id');
    }

    public function test_store_rejects_inactive_exam_type(): void
    {
        $academicYear = AcademicYear::factory()->create();
        $examType = ExamType::factory()->create([
            'school_id' => $this->school->id,
            'is_active' => false,
        ]);

        $this->post(route('admin.exam-packages.store'), [
            'school_id' => $this->school->id,
            'academic_year_id' => $academicYear->id,
            'exam_type_id' => $examType->id,
            'code' => 'PTS-2026',
            'name' => 'PTS Ganjil',
            'start_date' => '2026-09-14',
            'end_date' => '2026-09-19',
            'status' => ExamPackageStatus::Draft->value,
        ])->assertSessionHasErrors('exam_type_id');

        $this->assertDatabaseCount('exam_packages', 0);
    }

    public function test_store_cannot_set_active_status_for_an_inactive_exam_type(): void
    {
        $academicYear = AcademicYear::factory()->create();
        $examType = ExamType::factory()->create([
            'school_id' => $this->school->id,
            'is_active' => false,
        ]);

        $this->post(route('admin.exam-packages.store'), [
            'school_id' => $this->school->id,
            'academic_year_id' => $academicYear->id,
            'exam_type_id' => $examType->id,
            'code' => 'PTS-2026',
            'name' => 'PTS Ganjil',
            'start_date' => '2026-09-14',
            'end_date' => '2026-09-19',
            'status' => ExamPackageStatus::Aktif->value,
        ])->assertSessionHasErrors(['exam_type_id', 'status']);

        $this->assertDatabaseCount('exam_packages', 0);
    }

    public function test_school_admin_cannot_store_for_an_inaccessible_school(): void
    {
        $schoolAdmin = User::factory()->guru()->create();
        $this->school->update(['admin_id' => $schoolAdmin->id]);
        $otherSchool = School::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        $otherType = ExamType::factory()->create(['school_id' => $otherSchool->id, 'is_active' => true]);

        $this->actingAs($schoolAdmin)
            ->post(route('admin.exam-packages.store'), [
                'school_id' => $otherSchool->id,
                'academic_year_id' => $academicYear->id,
                'exam_type_id' => $otherType->id,
                'code' => 'PTS-2026',
                'name' => 'PTS',
                'start_date' => '2026-09-14',
                'end_date' => '2026-09-19',
                'status' => ExamPackageStatus::Draft->value,
            ])
            ->assertForbidden();
    }

    public function test_headmaster_can_view_but_cannot_manage_packages(): void
    {
        $headmaster = User::factory()->guru()->create();
        $headmasterSchool = School::factory()->create(['headmaster_id' => $headmaster->id]);
        $package = $this->packageFor($headmasterSchool);

        $this->actingAs($headmaster)
            ->get(route('admin.exam-packages.index'))
            ->assertOk();

        $this->actingAs($headmaster)
            ->delete(route('admin.exam-packages.destroy', $package))
            ->assertForbidden();
    }

    public function test_update_changes_package_but_not_school(): void
    {
        $academicYear = AcademicYear::factory()->create();
        $examType = ExamType::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $package = $this->packageFor($this->school, $academicYear, $examType, [
            'code' => 'PTS-2026',
            'name' => 'PTS Lama',
        ]);
        $otherSchool = School::factory()->create();

        $this->put(route('admin.exam-packages.update', $package), [
            'school_id' => $otherSchool->id,
            'academic_year_id' => $academicYear->id,
            'exam_type_id' => $examType->id,
            'code' => 'PTS-BARU',
            'name' => 'PTS Baru',
            'description' => 'Deskripsi baru.',
            'start_date' => '2026-09-15',
            'end_date' => '2026-09-20',
            'status' => ExamPackageStatus::Aktif->value,
        ])->assertRedirect()->assertSessionHas('toast.type', 'success');

        $package->refresh();

        $this->assertSame($this->school->id, $package->school_id);
        $this->assertSame('PTS-BARU', $package->code);
        $this->assertSame('PTS Baru', $package->name);
        $this->assertSame(ExamPackageStatus::Aktif, $package->status);
    }

    public function test_update_rejects_switching_to_an_inactive_exam_type(): void
    {
        $activeExamType = ExamType::factory()->create([
            'school_id' => $this->school->id,
            'is_active' => true,
        ]);
        $inactiveExamType = ExamType::factory()->create([
            'school_id' => $this->school->id,
            'is_active' => false,
        ]);
        $package = $this->packageFor($this->school, examType: $activeExamType);

        $this->put(route('admin.exam-packages.update', $package), [
            'exam_type_id' => $inactiveExamType->id,
        ])->assertSessionHasErrors('exam_type_id');

        $this->assertSame($activeExamType->id, $package->fresh()->exam_type_id);
    }

    public function test_update_allows_keeping_the_current_inactive_exam_type(): void
    {
        $inactiveExamType = ExamType::factory()->create([
            'school_id' => $this->school->id,
            'is_active' => false,
        ]);
        $package = $this->packageFor($this->school, examType: $inactiveExamType);

        $this->put(route('admin.exam-packages.update', $package), [
            'exam_type_id' => $inactiveExamType->id,
            'name' => 'PTS Ganjil',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame($inactiveExamType->id, $package->fresh()->exam_type_id);
    }

    public function test_update_cannot_activate_package_while_its_exam_type_is_inactive(): void
    {
        $inactiveExamType = ExamType::factory()->create([
            'school_id' => $this->school->id,
            'is_active' => false,
        ]);
        $package = $this->packageFor($this->school, examType: $inactiveExamType);

        $this->put(route('admin.exam-packages.update', $package), [
            'exam_type_id' => $inactiveExamType->id,
            'status' => ExamPackageStatus::Aktif->value,
        ])->assertSessionHasErrors('status');

        $this->assertSame(ExamPackageStatus::Draft, $package->fresh()->status);
    }

    public function test_update_keeps_the_academic_year(): void
    {
        $package = $this->packageFor($this->school);
        $this->scheduleFor($package);
        $otherYear = AcademicYear::factory()->inactive()->create();

        $this->put(route('admin.exam-packages.update', $package), [
            'academic_year_id' => $otherYear->id,
            'name' => 'PTS Ganjil',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame($this->academicYear->id, $package->fresh()->academic_year_id);
    }

    public function test_update_cannot_shrink_period_past_existing_schedules(): void
    {
        $package = $this->packageFor($this->school);
        $this->scheduleFor($package);

        $this->put(route('admin.exam-packages.update', $package), [
            'start_date' => '2026-09-14',
            'end_date' => '2026-09-14',
        ])->assertSessionHasErrors('end_date');

        $this->assertSame('2026-09-19', $package->fresh()->end_date->toDateString());
    }

    public function test_destroy_deletes_package_and_its_schedules(): void
    {
        $package = $this->packageFor($this->school);
        $schedule = $this->scheduleFor($package);

        $this->delete(route('admin.exam-packages.destroy', $package))
            ->assertRedirect()
            ->assertSessionHas('toast.type', 'success');

        $this->assertDatabaseMissing('exam_packages', ['id' => $package->id]);
        $this->assertDatabaseMissing('exam_schedules', ['id' => $schedule->id]);
    }

    private function packageFor(
        School $school,
        ?AcademicYear $academicYear = null,
        ?ExamType $examType = null,
        array $attributes = [],
        ?ExamPackageStatus $status = null,
    ): ExamPackage {
        $academicYear ??= $this->academicYear;
        $examType ??= ExamType::factory()->create(['school_id' => $school->id, 'is_active' => true]);

        return ExamPackage::factory()->create(array_merge([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'exam_type_id' => $examType->id,
            'start_date' => '2026-09-14',
            'end_date' => '2026-09-19',
            'status' => $status ?? ExamPackageStatus::Draft,
        ], $attributes));
    }

    private function scheduleFor(ExamPackage $package): ExamSchedule
    {
        $class = SchoolClass::factory()->create([
            'school_id' => $package->school_id,
            'academic_year_id' => $package->academic_year_id,
        ]);
        $subject = Subject::factory()->create();
        $session = Session::factory()->create(['school_id' => $package->school_id]);
        $room = Room::factory()->create(['school_id' => $package->school_id]);

        return ExamSchedule::factory()->forPackage($package)->create([
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'session_id' => $session->id,
            'room_id' => $room->id,
            'exam_date' => '2026-09-15',
        ]);
    }
}
