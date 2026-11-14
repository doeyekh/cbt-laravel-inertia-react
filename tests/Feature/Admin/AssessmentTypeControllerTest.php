<?php

namespace Tests\Feature\Admin;

use App\Models\AssessmentType;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AssessmentTypeControllerTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->actingAs(User::factory()->administrator()->create());
    }

    public function test_index_returns_paginated_assessment_types_with_weights(): void
    {
        AssessmentType::factory()->count(7)->create([
            'school_id' => $this->school->id,
        ]);

        $this->get(route('admin.assessment-types.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('AssessmentTypes/Index')
                ->has('assessmentTypes.data', 5)
                ->where('assessmentTypes.total', 7)
                ->where('filters.search', '')
                ->has('schools')
                ->has('stats')
            );
    }

    public function test_index_filters_by_code_or_name(): void
    {
        AssessmentType::factory()->create([
            'school_id' => $this->school->id,
            'code' => 'FORMATIF',
            'name' => 'Penilaian Formatif',
        ]);
        AssessmentType::factory()->create([
            'school_id' => $this->school->id,
            'code' => 'SUMATIF',
            'name' => 'Penilaian Sumatif',
        ]);

        $this->get(route('admin.assessment-types.index', ['search' => 'FORMATIF']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('assessmentTypes.data', 1)
                ->where('assessmentTypes.data.0.code', 'FORMATIF')
            );
    }

    public function test_index_filters_by_school(): void
    {
        $otherSchool = School::factory()->create();
        $assessmentType = AssessmentType::factory()->create([
            'school_id' => $this->school->id,
        ]);
        AssessmentType::factory()->create(['school_id' => $otherSchool->id]);

        $this->get(route('admin.assessment-types.index', ['school_id' => $this->school->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('assessmentTypes.data', 1)
                ->where('assessmentTypes.data.0.id', $assessmentType->id)
                ->where('filters.school_id', $this->school->id)
            );
    }

    public function test_index_includes_weight_and_status_stats(): void
    {
        $active = AssessmentType::factory()->create([
            'school_id' => $this->school->id,
            'weight' => 40,
            'is_active' => true,
        ]);
        AssessmentType::factory()->create([
            'school_id' => $this->school->id,
            'weight' => 60,
            'is_active' => false,
        ]);

        $this->get(route('admin.assessment-types.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.total', 2)
                ->where('stats.inactive', 1)
                ->where('stats.total_weight', 100)
                ->where('stats.active.id', $active->id)
                ->where('stats.active.weight', 40)
            );
    }

    public function test_index_scopes_assessment_types_to_the_accessible_schools(): void
    {
        $headmaster = User::factory()->guru()->create();
        $mySchool = School::factory()->create(['headmaster_id' => $headmaster->id]);
        $otherSchool = School::factory()->create();

        $mine = AssessmentType::factory()->create(['school_id' => $mySchool->id]);
        AssessmentType::factory()->create(['school_id' => $otherSchool->id]);

        $this->actingAs($headmaster)
            ->get(route('admin.assessment-types.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('assessmentTypes.data', 1)
                ->where('assessmentTypes.data.0.id', $mine->id)
                ->where('stats.total', 1)
            );
    }

    public function test_index_forbids_regular_guru(): void
    {
        $this->actingAs(User::factory()->guru()->create())
            ->get(route('admin.assessment-types.index'))
            ->assertForbidden();
    }

    public function test_store_creates_assessment_type_with_weight(): void
    {
        $this->post(route('admin.assessment-types.store'), [
            'school_id' => $this->school->id,
            'code' => 'FORMATIF',
            'name' => 'Penilaian Formatif',
            'description' => 'Penilaian selama proses pembelajaran.',
            'weight' => 25.5,
        ])->assertRedirect();

        $this->assertDatabaseHas('assessment_types', [
            'school_id' => $this->school->id,
            'code' => 'FORMATIF',
            'name' => 'Penilaian Formatif',
            'description' => 'Penilaian selama proses pembelajaran.',
            'weight' => 25.5,
            'is_active' => false,
        ]);
    }

    public function test_store_requires_a_school(): void
    {
        $this->post(route('admin.assessment-types.store'), [
            'code' => 'FORMATIF',
            'name' => 'Test',
            'weight' => 25,
        ])->assertSessionHasErrors('school_id');
    }

    public function test_store_validates_weight_range(): void
    {
        $this->post(route('admin.assessment-types.store'), [
            'school_id' => $this->school->id,
            'code' => 'FORMATIF',
            'name' => 'Test',
            'weight' => 101,
        ])->assertSessionHasErrors('weight');
    }

    public function test_store_requires_weight(): void
    {
        $this->post(route('admin.assessment-types.store'), [
            'school_id' => $this->school->id,
            'code' => 'FORMATIF',
            'name' => 'Test',
        ])->assertSessionHasErrors('weight');
    }

    public function test_store_rejects_duplicate_code_in_the_same_school(): void
    {
        AssessmentType::factory()->create([
            'school_id' => $this->school->id,
            'code' => 'FORMATIF',
        ]);

        $this->post(route('admin.assessment-types.store'), [
            'school_id' => $this->school->id,
            'code' => 'FORMATIF',
            'name' => 'Test',
            'weight' => 25,
        ])->assertSessionHasErrors('code');
    }

    public function test_store_allows_the_same_code_in_a_different_school(): void
    {
        $otherSchool = School::factory()->create();

        AssessmentType::factory()->create([
            'school_id' => $otherSchool->id,
            'code' => 'FORMATIF',
        ]);

        $this->post(route('admin.assessment-types.store'), [
            'school_id' => $this->school->id,
            'code' => 'FORMATIF',
            'name' => 'Test',
            'weight' => 25,
        ])->assertRedirect();
    }

    public function test_headmaster_can_view_but_cannot_manage_assessment_types(): void
    {
        $headmaster = User::factory()->guru()->create();
        $school = School::factory()->create(['headmaster_id' => $headmaster->id]);

        $this->actingAs($headmaster)
            ->get(route('admin.assessment-types.index'))
            ->assertOk();

        $this->actingAs($headmaster)
            ->post(route('admin.assessment-types.store'), [
                'school_id' => $school->id,
                'code' => 'FORMATIF',
                'name' => 'Test',
                'weight' => 25,
            ])
            ->assertForbidden();
    }

    public function test_toggle_status_activates_assessment_type_without_affecting_others(): void
    {
        $previous = AssessmentType::factory()->create(['is_active' => true]);
        $current = AssessmentType::factory()->create(['is_active' => false]);

        $this->patch(route('admin.assessment-types.toggle-status', $current))
            ->assertRedirect()
            ->assertSessionHas('toast.type', 'success');

        $this->assertTrue($previous->fresh()->is_active);
        $this->assertTrue($current->fresh()->is_active);
        $this->assertSame(2, AssessmentType::query()->where('is_active', true)->count());
    }

    public function test_update_updates_weight_and_other_fields(): void
    {
        $assessmentType = AssessmentType::factory()->create([
            'code' => 'FORMATIF',
            'name' => 'Formatif',
            'weight' => 20,
        ]);

        $this->put(route('admin.assessment-types.update', $assessmentType), [
            'code' => 'SUMATIF',
            'name' => 'Sumatif',
            'description' => 'Penilaian akhir.',
            'weight' => 45.5,
        ])->assertRedirect();

        $this->assertDatabaseHas('assessment_types', [
            'id' => $assessmentType->id,
            'code' => 'SUMATIF',
            'name' => 'Sumatif',
            'description' => 'Penilaian akhir.',
            'weight' => 45.5,
        ]);
    }

    public function test_update_validates_code_unique_within_the_school(): void
    {
        AssessmentType::factory()->create([
            'school_id' => $this->school->id,
            'code' => 'SUMATIF',
        ]);

        $assessmentType = AssessmentType::factory()->create([
            'school_id' => $this->school->id,
            'code' => 'FORMATIF',
        ]);

        $this->put(route('admin.assessment-types.update', $assessmentType), [
            'code' => 'SUMATIF',
            'name' => 'Updated',
            'weight' => 30,
        ])->assertSessionHasErrors('code');
    }

    public function test_destroy_deletes_assessment_type(): void
    {
        $assessmentType = AssessmentType::factory()->create();

        $this->delete(route('admin.assessment-types.destroy', $assessmentType))
            ->assertRedirect();

        $this->assertDatabaseMissing('assessment_types', [
            'id' => $assessmentType->id,
        ]);
    }
}
