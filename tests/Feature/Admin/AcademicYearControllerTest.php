<?php

namespace Tests\Feature\Admin;

use App\Enums\AcademicYearSemester;
use App\Models\AcademicYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AcademicYearControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->administrator()->create());
    }

    public function test_index_returns_paginated_academic_years(): void
    {
        AcademicYear::factory()->count(7)->create();

        $this->get(route('admin.academic-years.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('AcademicYears/Index')
                ->has('academicYears.data', 5)
                ->where('academicYears.total', 7)
                ->where('filters.search', '')
                ->has('semesters', 2)
            );
    }

    public function test_index_filters_by_name_or_code(): void
    {
        AcademicYear::factory()->ganjil()->create(['name' => '2024/2025']);
        AcademicYear::factory()->ganjil()->create(['name' => '2023/2024']);

        $this->get(route('admin.academic-years.index', ['search' => '20241']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('academicYears.data', 1)
                ->where('academicYears.data.0.code', '20241')
            );
    }

    public function test_index_includes_academic_year_stats(): void
    {
        $active = AcademicYear::factory()->ganjil()->active()->create(['name' => '2024/2025']);
        AcademicYear::factory()->genap()->create(['name' => '2023/2024']);

        $this->get(route('admin.academic-years.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.total', 2)
                ->where('stats.inactive', 1)
                ->where('stats.active.id', $active->id)
                ->where('stats.active.code', $active->code)
                ->where('stats.active.name', '2024/2025')
                ->where('stats.active.semester_label', 'Ganjil')
                ->where('stats.active.is_active', true)
            );
    }

    public function test_store_creates_academic_year_with_generated_code(): void
    {
        $this->post(route('admin.academic-years.store'), [
            'name' => '2024/2025',
            'semester' => AcademicYearSemester::Ganjil->value,
        ])->assertRedirect();

        $this->assertDatabaseHas('academic_years', [
            'code' => '20241',
            'name' => '2024/2025',
            'semester' => AcademicYearSemester::Ganjil->value,
            'is_active' => false,
        ]);
    }

    public function test_store_always_creates_inactive_academic_year(): void
    {
        $previous = AcademicYear::factory()->ganjil()->active()->create(['name' => '2023/2024']);

        $this->post(route('admin.academic-years.store'), [
            'name' => '2024/2025',
            'semester' => 'ganjil',
            'is_active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('academic_years', [
            'name' => '2024/2025',
            'semester' => 'ganjil',
            'is_active' => false,
        ]);
        $this->assertTrue($previous->fresh()->is_active);
    }

    public function test_toggle_status_activates_year_and_deactivates_previous_active_year(): void
    {
        $previous = AcademicYear::factory()->ganjil()->active()->create(['name' => '2023/2024']);
        $current = AcademicYear::factory()->ganjil()->create(['name' => '2024/2025']);

        $this->patch(route('admin.academic-years.toggle-status', $current))
            ->assertRedirect()
            ->assertSessionHas('toast.type', 'success');

        $this->assertFalse($previous->fresh()->is_active);
        $this->assertTrue($current->fresh()->is_active);
        $this->assertSame(1, AcademicYear::query()->where('is_active', true)->count());
    }

    public function test_toggle_status_deactivates_active_year(): void
    {
        $academicYear = AcademicYear::factory()->ganjil()->active()->create(['name' => '2024/2025']);

        $this->patch(route('admin.academic-years.toggle-status', $academicYear))
            ->assertRedirect();

        $this->assertFalse($academicYear->fresh()->is_active);
        $this->assertSame(0, AcademicYear::query()->where('is_active', true)->count());
    }

    public function test_store_rejects_duplicate_name_and_semester(): void
    {
        AcademicYear::factory()->ganjil()->create(['name' => '2024/2025']);

        $this->post(route('admin.academic-years.store'), [
            'name' => '2024/2025',
            'semester' => 'ganjil',
        ])->assertSessionHasErrors('name');
    }

    public function test_store_rejects_non_sequential_school_year(): void
    {
        $this->post(route('admin.academic-years.store'), [
            'name' => '2024/2026',
            'semester' => 'ganjil',
        ])->assertSessionHasErrors('name');

        $this->assertDatabaseCount('academic_years', 0);
    }

    public function test_generate_creates_even_semester_academic_year_for_january_to_june(): void
    {
        $this->travelTo('2026-06-30 10:00:00');

        $this->post(route('admin.academic-years.generate'))
            ->assertRedirect()
            ->assertSessionHas('toast.type', 'success');

        $this->assertDatabaseHas('academic_years', [
            'code' => '20252',
            'name' => '2025/2026',
            'semester' => AcademicYearSemester::Genap->value,
            'is_active' => false,
        ]);
    }

    public function test_generate_creates_odd_semester_academic_year_for_july_to_december(): void
    {
        $this->travelTo('2026-07-01 10:00:00');

        $this->post(route('admin.academic-years.generate'))
            ->assertRedirect()
            ->assertSessionHas('toast.type', 'success');

        $this->assertDatabaseHas('academic_years', [
            'code' => '20261',
            'name' => '2026/2027',
            'semester' => AcademicYearSemester::Ganjil->value,
            'is_active' => false,
        ]);
    }

    public function test_generate_does_not_insert_when_academic_year_already_exists(): void
    {
        $this->travelTo('2026-11-09 10:00:00');

        $existing = AcademicYear::factory()->ganjil()->create(['name' => '2026/2027']);

        $this->post(route('admin.academic-years.generate'))
            ->assertRedirect()
            ->assertSessionHas('toast.type', 'info');

        $this->assertDatabaseCount('academic_years', 1);
        $this->assertDatabaseHas('academic_years', [
            'id' => $existing->id,
            'code' => '20261',
        ]);
    }

    public function test_update_regenerates_code(): void
    {
        $academicYear = AcademicYear::factory()->ganjil()->create(['name' => '2024/2025']);

        $this->put(route('admin.academic-years.update', $academicYear), [
            'name' => '2024/2025',
            'semester' => 'genap',
        ])->assertRedirect();

        $this->assertSame('20242', $academicYear->fresh()->code);
    }

    public function test_update_allows_keeping_same_name_and_semester(): void
    {
        $academicYear = AcademicYear::factory()->ganjil()->active()->create(['name' => '2024/2025']);

        $this->put(route('admin.academic-years.update', $academicYear), [
            'name' => '2024/2025',
            'semester' => 'ganjil',
        ])->assertSessionHasNoErrors();

        $this->assertTrue($academicYear->fresh()->is_active);
    }

    public function test_destroy_deletes_academic_year(): void
    {
        $academicYear = AcademicYear::factory()->create();

        $this->delete(route('admin.academic-years.destroy', $academicYear))
            ->assertRedirect();

        $this->assertDatabaseMissing('academic_years', [
            'id' => $academicYear->id,
        ]);
    }
}
