<?php

namespace Tests\Feature\Admin;

use App\Models\School;
use App\Models\Session;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SessionControllerTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->actingAs(User::factory()->administrator()->create());
    }

    public function test_index_returns_paginated_sessions(): void
    {
        Session::factory()->count(7)->create();

        $this->get(route('admin.sessions.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('sessions.data', 5)
                ->where('sessions.total', 7)
                ->where('filters.search', '')
                ->has('schools')
                ->has('stats')
            );
    }

    public function test_index_filters_by_code_or_name(): void
    {
        Session::factory()->create(['code' => 'SESS01', 'name' => 'Sesi Pagi']);
        Session::factory()->create(['code' => 'SESS02', 'name' => 'Sesi Siang']);

        $this->get(route('admin.sessions.index', ['search' => 'SESS01']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('sessions.data', 1)
                ->where('sessions.data.0.code', 'SESS01')
            );
    }

    public function test_index_filters_by_school(): void
    {
        $otherSchool = School::factory()->create();
        $session = Session::factory()->create(['school_id' => $this->school->id]);
        Session::factory()->create(['school_id' => $otherSchool->id]);

        $this->get(route('admin.sessions.index', ['school_id' => $this->school->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('sessions.data', 1)
                ->where('sessions.data.0.id', $session->id)
                ->where('filters.school_id', $this->school->id)
            );
    }

    public function test_index_includes_session_stats(): void
    {
        $active = Session::factory()->create([
            'school_id' => $this->school->id,
            'is_active' => true,
        ]);
        Session::factory()->create([
            'school_id' => $this->school->id,
            'is_active' => false,
        ]);

        $this->get(route('admin.sessions.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.total', 2)
                ->where('stats.active.id', $active->id)
                ->where('stats.active.code', $active->code)
                ->where('stats.active.name', $active->name)
                ->where('stats.active.is_active', true)
            );
    }

    public function test_index_scopes_sessions_to_the_accessible_schools(): void
    {
        $headmaster = User::factory()->guru()->create();
        $mySchool = School::factory()->create(['headmaster_id' => $headmaster->id]);
        $otherSchool = School::factory()->create();

        $mine = Session::factory()->create(['school_id' => $mySchool->id]);
        Session::factory()->create(['school_id' => $otherSchool->id]);

        $this->actingAs($headmaster)
            ->get(route('admin.sessions.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('sessions.data', 1)
                ->where('sessions.data.0.id', $mine->id)
            );
    }

    public function test_index_forbids_regular_guru(): void
    {
        $this->actingAs(User::factory()->guru()->create())
            ->get(route('admin.sessions.index'))
            ->assertForbidden();
    }

    public function test_store_creates_session_for_the_selected_school(): void
    {
        $this->post(route('admin.sessions.store'), [
            'school_id' => $this->school->id,
            'code' => 'SESS01',
            'name' => 'Sesi Pagi',
            'start_time' => '07:00:00',
            'end_time' => '09:00:00',
        ])->assertRedirect();

        $this->assertDatabaseHas('exam_sessions', [
            'school_id' => $this->school->id,
            'code' => 'SESS01',
            'name' => 'Sesi Pagi',
            'start_time' => '07:00:00',
            'end_time' => '09:00:00',
            'is_active' => false,
        ]);
    }

    public function test_store_requires_a_school(): void
    {
        $this->post(route('admin.sessions.store'), [
            'code' => 'SESS01',
            'name' => 'Test',
            'start_time' => '07:00:00',
            'end_time' => '09:00:00',
        ])->assertSessionHasErrors('school_id');
    }

    public function test_store_validates_code_required(): void
    {
        $this->post(route('admin.sessions.store'), [
            'school_id' => $this->school->id,
            'name' => 'Test',
        ])->assertSessionHasErrors('code');
    }

    public function test_store_validates_code_regex(): void
    {
        $this->post(route('admin.sessions.store'), [
            'school_id' => $this->school->id,
            'code' => 'sess-01',
            'name' => 'Test',
            'start_time' => '07:00:00',
            'end_time' => '09:00:00',
        ])->assertSessionHasErrors('code');
    }

    public function test_store_rejects_duplicate_code_in_the_same_school(): void
    {
        Session::factory()->create([
            'school_id' => $this->school->id,
            'code' => 'SESS01',
            'is_active' => true,
        ]);

        $this->post(route('admin.sessions.store'), [
            'school_id' => $this->school->id,
            'code' => 'SESS01',
            'name' => 'Test',
            'start_time' => '07:00:00',
            'end_time' => '09:00:00',
        ])->assertSessionHasErrors('code');
    }

    public function test_store_allows_the_same_code_in_a_different_school(): void
    {
        $otherSchool = School::factory()->create();

        Session::factory()->create([
            'school_id' => $otherSchool->id,
            'code' => 'SESS01',
        ]);

        $this->post(route('admin.sessions.store'), [
            'school_id' => $this->school->id,
            'code' => 'SESS01',
            'name' => 'Test',
            'start_time' => '07:00:00',
            'end_time' => '09:00:00',
        ])->assertRedirect();
    }

    public function test_store_validates_end_time_after_start_time(): void
    {
        $this->post(route('admin.sessions.store'), [
            'school_id' => $this->school->id,
            'code' => 'SESS01',
            'name' => 'Test',
            'start_time' => '09:00:00',
            'end_time' => '07:00:00',
        ])->assertSessionHasErrors('end_time');
    }

    public function test_headmaster_can_view_but_cannot_manage_sessions(): void
    {
        $headmaster = User::factory()->guru()->create();
        $school = School::factory()->create(['headmaster_id' => $headmaster->id]);

        $this->actingAs($headmaster)
            ->get(route('admin.sessions.index'))
            ->assertOk();

        $this->actingAs($headmaster)
            ->post(route('admin.sessions.store'), [
                'school_id' => $school->id,
                'code' => 'SESS01',
                'name' => 'Test',
                'start_time' => '07:00:00',
                'end_time' => '09:00:00',
            ])
            ->assertForbidden();
    }

    public function test_toggle_status_activates_session_without_affecting_others(): void
    {
        $previous = Session::factory()->create(['is_active' => true]);
        $current = Session::factory()->create(['is_active' => false]);

        $this->patch(route('admin.sessions.toggle-status', $current))
            ->assertRedirect()
            ->assertSessionHas('toast.type', 'success');

        $this->assertTrue($previous->fresh()->is_active);
        $this->assertTrue($current->fresh()->is_active);
        $this->assertSame(2, Session::query()->where('is_active', true)->count());
    }

    public function test_toggle_status_deactivates_active_session(): void
    {
        $session = Session::factory()->create(['is_active' => true]);

        $this->patch(route('admin.sessions.toggle-status', $session))
            ->assertRedirect();

        $this->assertFalse($session->fresh()->is_active);
        $this->assertSame(0, Session::query()->where('is_active', true)->count());
    }

    public function test_update_validates_and_updates_session(): void
    {
        $session = Session::factory()->create(['code' => 'SESS01', 'name' => 'Sesi Pagi']);

        $this->put(route('admin.sessions.update', $session), [
            'code' => 'SESS02',
            'name' => 'Sesi Siang',
            'start_time' => '09:30:00',
            'end_time' => '11:30:00',
        ])->assertRedirect();

        $this->assertDatabaseHas('exam_sessions', [
            'id' => $session->id,
            'code' => 'SESS02',
            'name' => 'Sesi Siang',
            'start_time' => '09:30:00',
            'end_time' => '11:30:00',
        ]);
    }

    public function test_update_validates_code_unique_within_the_school(): void
    {
        Session::factory()->create([
            'school_id' => $this->school->id,
            'code' => 'SESS02',
            'is_active' => true,
        ]);

        $session = Session::factory()->create([
            'school_id' => $this->school->id,
            'code' => 'SESS01',
            'name' => 'SESS01',
        ]);

        $this->put(route('admin.sessions.update', $session), [
            'code' => 'SESS02',
            'name' => 'Updated',
            'start_time' => '07:00:00',
            'end_time' => '09:00:00',
        ])->assertSessionHasErrors('code');
    }

    public function test_destroy_deletes_session(): void
    {
        $session = Session::factory()->create();

        $this->delete(route('admin.sessions.destroy', $session))
            ->assertRedirect();

        $this->assertDatabaseMissing('exam_sessions', [
            'id' => $session->id,
        ]);
    }
}
