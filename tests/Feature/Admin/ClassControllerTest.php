<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ClassControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->administrator()->create());
    }

    public function test_index_returns_paginated_classes(): void
    {
        $activeAcademicYear = AcademicYear::factory()->create(['is_active' => true]);

        SchoolClass::factory()->count(7)->create(['academic_year_id' => $activeAcademicYear->id]);

        $this->get(route('admin.classes.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Classes/Index')
                ->has('classes.data', 5)
                ->where('classes.total', 7)
                ->where('filters.search', '')
                ->has('stats')
                ->where('stats.total', 7)
                ->has('schools')
                ->has('academicYears')
            );
    }

    public function test_index_filters_by_code_name_school_or_academic_year(): void
    {
        $school = School::factory()->create(['code' => 'YAS-001', 'name' => 'SMA Nusantara']);
        $academicYear = AcademicYear::factory()->create(['name' => '2024/2025', 'semester' => 'ganjil', 'code' => '20241', 'is_active' => true]);

        SchoolClass::factory()->create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'code' => 'KLS-01',
            'name' => 'X IPA 1',
        ]);

        $this->get(route('admin.classes.index', ['search' => 'KLS-01']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('classes.data', 1)
                ->where('classes.data.0.code', 'KLS-01')
            );

        $this->get(route('admin.classes.index', ['search' => 'SMA']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('classes.data', 1)
                ->where('classes.data.0.school_name', 'SMA Nusantara')
            );
    }

    public function test_index_includes_class_stats(): void
    {
        $activeAcademicYear = AcademicYear::factory()->create(['is_active' => true]);

        SchoolClass::factory()->create(['academic_year_id' => $activeAcademicYear->id, 'is_active' => true]);
        SchoolClass::factory()->create(['academic_year_id' => $activeAcademicYear->id, 'is_active' => false]);

        $this->get(route('admin.classes.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.total', 2)
                ->where('stats.active', 1)
                ->where('stats.inactive', 1)
            );
    }

    public function test_index_scopes_classes_to_the_accessible_schools(): void
    {
        $headmaster = User::factory()->guru()->create();
        $mySchool = School::factory()->create([
            'name' => 'Sekolah Saya',
            'headmaster_id' => $headmaster->id,
        ]);
        $activeAcademicYear = AcademicYear::factory()->create(['is_active' => true]);
        $otherSchool = School::factory()->create(['name' => 'Sekolah Lain']);

        $myClass = SchoolClass::factory()->forSchool($mySchool, $activeAcademicYear->id)->create();
        SchoolClass::factory()->forSchool($otherSchool, $activeAcademicYear->id)->create();

        $this->actingAs($headmaster)
            ->get(route('admin.classes.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('classes.data', 1)
                ->where('classes.data.0.id', $myClass->id)
            );

        $this->actingAs($headmaster)
            ->get(route('admin.classes.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.total', 1)
                ->where('stats.inactive', 1)
            );
    }

    public function test_index_only_shows_classes_of_the_active_academic_year(): void
    {
        $activeAcademicYear = AcademicYear::factory()->create(['is_active' => true]);
        $previousAcademicYear = AcademicYear::factory()->create(['name' => '2023/2024', 'semester' => 'ganjil', 'code' => '20231']);

        SchoolClass::factory()->create(['academic_year_id' => $previousAcademicYear->id]);
        SchoolClass::factory()->create(['academic_year_id' => $activeAcademicYear->id]);

        $this->get(route('admin.classes.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('classes.data', 1)
                ->where('classes.data.0.academic_year_id', $activeAcademicYear->id)
            );
    }

    public function test_index_forbids_regular_guru(): void
    {
        $this->actingAs(User::factory()->guru()->create())
            ->get(route('admin.classes.index'))
            ->assertForbidden();
    }

    public function test_store_creates_inactive_class_with_active_academic_year(): void
    {
        $school = School::factory()->create(['code' => 'YAS-001']);
        $academicYear = AcademicYear::factory()->create(['name' => '2024/2025', 'semester' => 'ganjil', 'code' => '20241', 'is_active' => true]);

        $this->post(route('admin.classes.store'), [
            'school_id' => $school->id,
            'code' => 'KLS-01',
            'name' => 'X IPA 1',
        ])->assertRedirect();

        $schoolClass = SchoolClass::query()->where('code', 'KLS-01')->first();

        $this->assertNotNull($schoolClass);
        $this->assertSame($school->id, $schoolClass->school_id);
        $this->assertSame($academicYear->id, $schoolClass->academic_year_id);
        $this->assertFalse($schoolClass->is_active);
    }

    public function test_store_requires_school_code_and_name(): void
    {
        $this->post(route('admin.classes.store'), [])
            ->assertSessionHasErrors(['school_id', 'code', 'name']);
    }

    public function test_store_rejects_duplicate_code_within_the_school(): void
    {
        $school = School::factory()->create(['code' => 'YAS-001']);
        $academicYear = AcademicYear::factory()->create(['name' => '2024/2025', 'semester' => 'ganjil', 'code' => '20241']);

        SchoolClass::factory()->create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'code' => 'KLS-01',
            'name' => 'X IPA 1',
        ]);

        $this->post(route('admin.classes.store'), [
            'school_id' => $school->id,
            'code' => 'KLS-01',
            'name' => 'X IPA 2',
        ])->assertSessionHasErrors('code');
    }

    public function test_store_allows_same_code_in_different_schools(): void
    {
        $firstSchool = School::factory()->create(['code' => 'YAS-001']);
        $secondSchool = School::factory()->create(['code' => 'YAS-002']);
        AcademicYear::factory()->create(['is_active' => true]);

        $this->post(route('admin.classes.store'), [
            'school_id' => $firstSchool->id,
            'code' => 'KLS-01',
            'name' => 'X IPA 1',
        ])->assertRedirect();

        $this->post(route('admin.classes.store'), [
            'school_id' => $secondSchool->id,
            'code' => 'KLS-01',
            'name' => 'VII B',
        ])->assertRedirect();

        $this->assertDatabaseCount('classes', 2);
    }

    public function test_store_rejects_class_of_inaccessible_school(): void
    {
        $schoolAdmin = User::factory()->guru()->create();
        $mySchool = School::factory()->create([
            'name' => 'Sekolah Binaan',
            'admin_id' => $schoolAdmin->id,
        ]);
        AcademicYear::factory()->create(['is_active' => true]);

        $this->actingAs($schoolAdmin)
            ->post(route('admin.classes.store'), [
                'school_id' => $mySchool->id,
                'code' => 'KLS-01',
                'name' => 'X IPA 1',
            ])
            ->assertRedirect();

        $otherSchool = School::factory()->create(['name' => 'Sekolah Lain']);

        $this->actingAs($schoolAdmin)
            ->post(route('admin.classes.store'), [
                'school_id' => $otherSchool->id,
                'code' => 'KLS-02',
                'name' => 'XI IPA 1',
            ])
            ->assertSessionHasErrors('school_id');
    }

    public function test_update_updates_class_fields(): void
    {
        $school = School::factory()->create(['code' => 'YAS-001']);
        $academicYear = AcademicYear::factory()->create(['name' => '2024/2025', 'semester' => 'ganjil', 'code' => '20241']);
        $schoolClass = SchoolClass::factory()->create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'code' => 'KLS-01',
            'name' => 'X IPA 1',
            'is_active' => false,
        ]);

        $this->put(route('admin.classes.update', $schoolClass), [
            'code' => 'KLS-02',
            'name' => 'X IPA 2',
        ])->assertRedirect();

        $this->assertDatabaseHas('classes', [
            'id' => $schoolClass->id,
            'code' => 'KLS-02',
            'name' => 'X IPA 2',
            'academic_year_id' => $academicYear->id,
        ]);
        $this->assertFalse($schoolClass->fresh()->is_active);
    }

    public function test_update_validates_code_and_name_uniqueness(): void
    {
        $school = School::factory()->create(['code' => 'YAS-001']);
        $academicYear = AcademicYear::factory()->create(['name' => '2024/2025', 'semester' => 'ganjil', 'code' => '20241']);

        $schoolClass = SchoolClass::factory()->create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'code' => 'KLS-01',
            'name' => 'X IPA 1',
        ]);

        SchoolClass::factory()->create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'code' => 'KLS-02',
            'name' => 'X IPA 2',
        ]);

        $this->put(route('admin.classes.update', $schoolClass), [
            'code' => 'KLS-02',
            'name' => 'X IPA 2',
        ])->assertSessionHasErrors('code');

        $this->put(route('admin.classes.update', $schoolClass), [
            'code' => 'KLS-03',
            'name' => 'X IPA 2',
        ])->assertSessionHasErrors('name');
    }

    public function test_toggle_status_activates_and_deactivates_class(): void
    {
        $schoolClass = SchoolClass::factory()->create(['is_active' => false]);

        $this->patch(route('admin.classes.toggle-status', $schoolClass))
            ->assertRedirect()
            ->assertSessionHas('toast.type', 'success');

        $this->assertTrue($schoolClass->fresh()->is_active);

        $this->patch(route('admin.classes.toggle-status', $schoolClass))
            ->assertRedirect();

        $this->assertFalse($schoolClass->fresh()->is_active);
    }

    public function test_destroy_deletes_inactive_class(): void
    {
        $schoolClass = SchoolClass::factory()->create(['is_active' => false]);

        $this->delete(route('admin.classes.destroy', $schoolClass))
            ->assertRedirect();

        $this->assertDatabaseMissing('classes', [
            'id' => $schoolClass->id,
        ]);
    }

    public function test_destroy_rejects_active_class(): void
    {
        $schoolClass = SchoolClass::factory()->create(['is_active' => true]);

        $this->delete(route('admin.classes.destroy', $schoolClass))
            ->assertRedirect()
            ->assertSessionHas('toast.type', 'error');

        $this->assertDatabaseHas('classes', [
            'id' => $schoolClass->id,
        ]);
    }

    public function test_kepala_sekolah_can_view_but_cannot_manage_classes(): void
    {
        $headmaster = User::factory()->guru()->create();
        $school = School::factory()->create([
            'name' => 'Sekolah Saya',
            'headmaster_id' => $headmaster->id,
        ]);
        $activeAcademicYear = AcademicYear::factory()->create(['is_active' => true]);

        SchoolClass::factory()->forSchool($school, $activeAcademicYear->id)->create([
            'code' => 'KLS-01',
        ]);

        $this->actingAs($headmaster)
            ->get(route('admin.classes.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('classes.data', 1)
            );

        $this->actingAs($headmaster)
            ->post(route('admin.classes.store'), [
                'school_id' => $school->id,
                'code' => 'KLS-02',
                'name' => 'XI IPA 1',
            ])
            ->assertForbidden();

        $schoolClass = SchoolClass::query()->where('school_id', $school->id)->first();

        $this->actingAs($headmaster)
            ->put(route('admin.classes.update', $schoolClass), [
                'code' => 'KLS-03',
                'name' => 'XI IPA 2',
            ])
            ->assertForbidden();

        $this->actingAs($headmaster)
            ->patch(route('admin.classes.toggle-status', $schoolClass))
            ->assertForbidden();

        $this->actingAs($headmaster)
            ->delete(route('admin.classes.destroy', $schoolClass))
            ->assertForbidden();
    }
}
