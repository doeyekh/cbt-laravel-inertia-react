<?php

namespace Tests\Feature\Admin;

use App\Enums\ExamPackageStatus;
use App\Models\ExamPackage;
use App\Models\ExamType;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExamTypeControllerTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->actingAs(User::factory()->administrator()->create());
    }

    public function test_index_returns_paginated_exam_types(): void
    {
        ExamType::factory()->count(7)->create();

        $this->get(route('admin.exam-types.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('examTypes.data', 5)
                ->where('examTypes.total', 7)
                ->where('filters.search', '')
                ->has('schools')
                ->has('stats')
            );
    }

    public function test_index_filters_by_name_or_code(): void
    {
        ExamType::factory()->create(['code' => 'KTSP', 'name' => 'Kurikulum Tingkat Sekolah Dasar']);
        ExamType::factory()->create(['code' => 'UN', 'name' => 'Ujian Nasional']);

        $this->get(route('admin.exam-types.index', ['search' => 'KTSP']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('examTypes.data', 1)
                ->where('examTypes.data.0.code', 'KTSP')
            );
    }

    public function test_index_filters_by_school(): void
    {
        $otherSchool = School::factory()->create();
        $examType = ExamType::factory()->create(['school_id' => $this->school->id]);
        ExamType::factory()->create(['school_id' => $otherSchool->id]);

        $this->get(route('admin.exam-types.index', ['school_id' => $this->school->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('examTypes.data', 1)
                ->where('examTypes.data.0.id', $examType->id)
                ->where('filters.school_id', $this->school->id)
            );
    }

    public function test_index_includes_exam_type_stats(): void
    {
        $active = ExamType::factory()->create(['is_active' => true, 'school_id' => $this->school->id]);
        ExamType::factory()->create(['is_active' => false, 'school_id' => $this->school->id]);

        $this->get(route('admin.exam-types.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.total', 2)
                ->where('stats.active.id', $active->id)
                ->where('stats.active.code', $active->code)
                ->where('stats.active.name', $active->name)
                ->where('stats.active.is_active', true)
            );
    }

    public function test_index_scopes_exam_types_to_the_accessible_schools(): void
    {
        $headmaster = User::factory()->guru()->create();
        $mySchool = School::factory()->create(['headmaster_id' => $headmaster->id]);
        $otherSchool = School::factory()->create();

        $mine = ExamType::factory()->create(['school_id' => $mySchool->id]);
        ExamType::factory()->create(['school_id' => $otherSchool->id]);

        $this->actingAs($headmaster)
            ->get(route('admin.exam-types.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('examTypes.data', 1)
                ->where('examTypes.data.0.id', $mine->id)
                ->where('stats.total', 1)
            );
    }

    public function test_index_forbids_regular_guru(): void
    {
        $this->actingAs(User::factory()->guru()->create())
            ->get(route('admin.exam-types.index'))
            ->assertForbidden();
    }

    public function test_store_creates_exam_type_for_the_selected_school(): void
    {
        $this->post(route('admin.exam-types.store'), [
            'school_id' => $this->school->id,
            'code' => 'KTSP',
            'name' => 'Kurikulum Tingkat Sekolah Dasar',
            'description' => 'Penilaian berdasarkan kurikulum sekolah',
        ])->assertRedirect();

        $this->assertDatabaseHas('exam_types', [
            'school_id' => $this->school->id,
            'code' => 'KTSP',
            'name' => 'Kurikulum Tingkat Sekolah Dasar',
            'description' => 'Penilaian berdasarkan kurikulum sekolah',
            'is_active' => false,
        ]);
    }

    public function test_store_requires_a_school(): void
    {
        $this->post(route('admin.exam-types.store'), [
            'code' => 'KTSP',
            'name' => 'Test',
        ])->assertSessionHasErrors('school_id');
    }

    public function test_store_validates_code_required(): void
    {
        $this->post(route('admin.exam-types.store'), [
            'school_id' => $this->school->id,
            'name' => 'Test',
        ])->assertSessionHasErrors('code');
    }

    public function test_store_validates_code_regex(): void
    {
        $this->post(route('admin.exam-types.store'), [
            'school_id' => $this->school->id,
            'code' => 'kt-sp',
            'name' => 'Test',
        ])->assertSessionHasErrors('code');
    }

    public function test_store_rejects_duplicate_code_in_the_same_school(): void
    {
        ExamType::factory()->create([
            'school_id' => $this->school->id,
            'code' => 'KTSP',
            'is_active' => true,
        ]);

        $this->post(route('admin.exam-types.store'), [
            'school_id' => $this->school->id,
            'code' => 'KTSP',
            'name' => 'Test',
        ])->assertSessionHasErrors('code');
    }

    public function test_store_allows_the_same_code_in_a_different_school(): void
    {
        $otherSchool = School::factory()->create();

        ExamType::factory()->create([
            'school_id' => $otherSchool->id,
            'code' => 'KTSP',
        ]);

        $this->post(route('admin.exam-types.store'), [
            'school_id' => $this->school->id,
            'code' => 'KTSP',
            'name' => 'Test',
        ])->assertRedirect();

        $this->assertDatabaseHas('exam_types', [
            'school_id' => $this->school->id,
            'code' => 'KTSP',
        ]);
    }

    public function test_school_admin_cannot_store_for_an_inaccessible_school(): void
    {
        $schoolAdmin = User::factory()->guru()->create();
        $this->school->update(['admin_id' => $schoolAdmin->id]);
        $otherSchool = School::factory()->create();

        $this->actingAs($schoolAdmin)
            ->post(route('admin.exam-types.store'), [
                'school_id' => $otherSchool->id,
                'code' => 'KTSP',
                'name' => 'Test',
            ])
            ->assertForbidden();
    }

    public function test_headmaster_can_view_but_cannot_manage_exam_types(): void
    {
        $headmaster = User::factory()->guru()->create();
        $school = School::factory()->create(['headmaster_id' => $headmaster->id]);

        $this->actingAs($headmaster)
            ->get(route('admin.exam-types.index'))
            ->assertOk();

        $this->actingAs($headmaster)
            ->post(route('admin.exam-types.store'), [
                'school_id' => $school->id,
                'code' => 'KTSP',
                'name' => 'Test',
            ])
            ->assertForbidden();
    }

    public function test_toggle_status_activates_exam_type_without_affecting_others(): void
    {
        $previous = ExamType::factory()->create(['is_active' => true]);
        $current = ExamType::factory()->create(['is_active' => false]);

        $this->patch(route('admin.exam-types.toggle-status', $current))
            ->assertRedirect()
            ->assertSessionHas('toast.type', 'success');

        $this->assertTrue($previous->fresh()->is_active);
        $this->assertTrue($current->fresh()->is_active);
        $this->assertSame(2, ExamType::query()->where('is_active', true)->count());
    }

    public function test_toggle_status_deactivates_active_exam_type(): void
    {
        $examType = ExamType::factory()->create(['is_active' => true]);

        $this->patch(route('admin.exam-types.toggle-status', $examType))
            ->assertRedirect();

        $this->assertFalse($examType->fresh()->is_active);
        $this->assertSame(0, ExamType::query()->where('is_active', true)->count());
    }

    public function test_toggle_status_returns_related_exam_packages_to_draft(): void
    {
        $examType = ExamType::factory()->create([
            'is_active' => true,
            'school_id' => $this->school->id,
        ]);
        $package = ExamPackage::factory()->create([
            'school_id' => $this->school->id,
            'exam_type_id' => $examType->id,
            'status' => ExamPackageStatus::Aktif,
        ]);
        $otherPackage = ExamPackage::factory()->create([
            'exam_type_id' => ExamType::factory()->create(['is_active' => true])->id,
            'status' => ExamPackageStatus::Aktif,
        ]);

        $this->patch(route('admin.exam-types.toggle-status', $examType))
            ->assertRedirect()
            ->assertSessionHas('toast.type', 'success')
            ->assertSessionHas('toast.description', fn (string $description): bool => str_contains(
                $description,
                'paket ujian dikembalikan ke status draft'
            ));

        $this->assertSame(ExamPackageStatus::Draft, $package->fresh()->status);
        $this->assertSame(ExamPackageStatus::Aktif, $otherPackage->fresh()->status);
    }

    public function test_activating_exam_type_also_returns_exam_packages_to_draft(): void
    {
        $examType = ExamType::factory()->create(['is_active' => false]);
        $package = ExamPackage::factory()->create([
            'exam_type_id' => $examType->id,
            'status' => ExamPackageStatus::Selesai,
        ]);

        $this->patch(route('admin.exam-types.toggle-status', $examType))
            ->assertRedirect();

        $this->assertTrue($examType->fresh()->is_active);
        $this->assertSame(ExamPackageStatus::Draft, $package->fresh()->status);
    }

    public function test_destroy_is_blocked_when_exam_type_is_used_by_exam_packages(): void
    {
        $examType = ExamType::factory()->create();
        ExamPackage::factory()->create(['exam_type_id' => $examType->id]);

        $this->delete(route('admin.exam-types.destroy', $examType))
            ->assertRedirect()
            ->assertSessionHas('toast.type', 'error');

        $this->assertDatabaseHas('exam_types', ['id' => $examType->id]);
    }

    public function test_update_validates_and_updates_exam_type(): void
    {
        $examType = ExamType::factory()->create(['code' => 'KTSP', 'name' => 'Kurikulum']);

        $this->put(route('admin.exam-types.update', $examType), [
            'code' => 'UN',
            'name' => 'Ujian Nasional',
            'description' => 'Ujian yang diadakan aku',
        ])->assertRedirect();

        $this->assertDatabaseHas('exam_types', [
            'id' => $examType->id,
            'code' => 'UN',
            'name' => 'Ujian Nasional',
            'description' => 'Ujian yang diadakan aku',
        ]);
    }

    public function test_update_validates_code_unique_within_the_school(): void
    {
        ExamType::factory()->create([
            'school_id' => $this->school->id,
            'code' => 'UN',
            'is_active' => true,
        ]);

        $examType = ExamType::factory()->create([
            'school_id' => $this->school->id,
            'code' => 'KTSP',
            'name' => 'KTSP',
        ]);

        $this->put(route('admin.exam-types.update', $examType), [
            'code' => 'UN',
            'name' => 'Updated',
        ])->assertSessionHasErrors('code');
    }

    public function test_destroy_deletes_exam_type(): void
    {
        $examType = ExamType::factory()->create();

        $this->delete(route('admin.exam-types.destroy', $examType))
            ->assertRedirect();

        $this->assertDatabaseMissing('exam_types', [
            'id' => $examType->id,
        ]);
    }
}
