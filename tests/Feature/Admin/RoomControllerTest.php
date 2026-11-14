<?php

namespace Tests\Feature\Admin;

use App\Models\Room;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RoomControllerTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->actingAs(User::factory()->administrator()->create());
    }

    public function test_index_returns_paginated_rooms(): void
    {
        Room::factory()->count(7)->create();

        $this->get(route('admin.rooms.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('rooms.data', 5)
                ->where('rooms.total', 7)
                ->where('filters.search', '')
                ->has('schools')
                ->has('stats')
            );
    }

    public function test_index_filters_by_code_or_name(): void
    {
        Room::factory()->create(['code' => 'RM01', 'name' => 'Ruangan 1']);
        Room::factory()->create(['code' => 'RM02', 'name' => 'Ruangan 2']);

        $this->get(route('admin.rooms.index', ['search' => 'RM01']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('rooms.data', 1)
                ->where('rooms.data.0.code', 'RM01')
            );
    }

    public function test_index_filters_by_school(): void
    {
        $otherSchool = School::factory()->create();
        $room = Room::factory()->create(['school_id' => $this->school->id]);
        Room::factory()->create(['school_id' => $otherSchool->id]);

        $this->get(route('admin.rooms.index', ['school_id' => $this->school->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('rooms.data', 1)
                ->where('rooms.data.0.id', $room->id)
                ->where('filters.school_id', $this->school->id)
            );
    }

    public function test_index_includes_room_stats(): void
    {
        $active = Room::factory()->create([
            'school_id' => $this->school->id,
            'is_active' => true,
        ]);
        Room::factory()->create([
            'school_id' => $this->school->id,
            'is_active' => false,
        ]);

        $this->get(route('admin.rooms.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.total', 2)
                ->where('stats.active.id', $active->id)
                ->where('stats.active.code', $active->code)
                ->where('stats.active.name', $active->name)
                ->where('stats.active.is_active', true)
            );
    }

    public function test_index_scopes_rooms_to_the_accessible_schools(): void
    {
        $headmaster = User::factory()->guru()->create();
        $mySchool = School::factory()->create(['headmaster_id' => $headmaster->id]);
        $otherSchool = School::factory()->create();

        $mine = Room::factory()->create(['school_id' => $mySchool->id]);
        Room::factory()->create(['school_id' => $otherSchool->id]);

        $this->actingAs($headmaster)
            ->get(route('admin.rooms.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('rooms.data', 1)
                ->where('rooms.data.0.id', $mine->id)
            );
    }

    public function test_index_forbids_regular_guru(): void
    {
        $this->actingAs(User::factory()->guru()->create())
            ->get(route('admin.rooms.index'))
            ->assertForbidden();
    }

    public function test_store_creates_room_for_the_selected_school(): void
    {
        $this->post(route('admin.rooms.store'), [
            'school_id' => $this->school->id,
            'code' => 'RM01',
            'name' => 'Ruangan Ujian 1',
            'building' => 'Gedung A',
            'floor' => '1',
            'capacity' => 40,
        ])->assertRedirect();

        $this->assertDatabaseHas('rooms', [
            'school_id' => $this->school->id,
            'code' => 'RM01',
            'name' => 'Ruangan Ujian 1',
            'building' => 'Gedung A',
            'floor' => '1',
            'capacity' => 40,
            'is_active' => false,
        ]);
    }

    public function test_store_requires_a_school(): void
    {
        $this->post(route('admin.rooms.store'), [
            'code' => 'RM01',
            'name' => 'Test',
            'building' => 'Gedung A',
            'floor' => '1',
            'capacity' => 40,
        ])->assertSessionHasErrors('school_id');
    }

    public function test_store_validates_code_required(): void
    {
        $this->post(route('admin.rooms.store'), [
            'school_id' => $this->school->id,
            'name' => 'Test',
        ])->assertSessionHasErrors('code');
    }

    public function test_store_validates_code_regex(): void
    {
        $this->post(route('admin.rooms.store'), [
            'school_id' => $this->school->id,
            'code' => 'rm-01',
            'name' => 'Test',
            'building' => 'Gedung A',
            'floor' => '1',
            'capacity' => 40,
        ])->assertSessionHasErrors('code');
    }

    public function test_store_rejects_duplicate_code_in_the_same_school(): void
    {
        Room::factory()->create([
            'school_id' => $this->school->id,
            'code' => 'RM01',
            'is_active' => true,
        ]);

        $this->post(route('admin.rooms.store'), [
            'school_id' => $this->school->id,
            'code' => 'RM01',
            'name' => 'Test',
            'building' => 'Gedung A',
            'floor' => '1',
            'capacity' => 40,
        ])->assertSessionHasErrors('code');
    }

    public function test_store_allows_the_same_code_in_a_different_school(): void
    {
        $otherSchool = School::factory()->create();

        Room::factory()->create([
            'school_id' => $otherSchool->id,
            'code' => 'RM01',
        ]);

        $this->post(route('admin.rooms.store'), [
            'school_id' => $this->school->id,
            'code' => 'RM01',
            'name' => 'Test',
            'building' => 'Gedung A',
            'floor' => '1',
            'capacity' => 40,
        ])->assertRedirect();
    }

    public function test_store_validates_capacity_min(): void
    {
        $this->post(route('admin.rooms.store'), [
            'school_id' => $this->school->id,
            'code' => 'RM01',
            'name' => 'Test',
            'building' => 'Gedung A',
            'floor' => '1',
            'capacity' => 0,
        ])->assertSessionHasErrors('capacity');
    }

    public function test_headmaster_can_view_but_cannot_manage_rooms(): void
    {
        $headmaster = User::factory()->guru()->create();
        $school = School::factory()->create(['headmaster_id' => $headmaster->id]);

        $this->actingAs($headmaster)
            ->get(route('admin.rooms.index'))
            ->assertOk();

        $this->actingAs($headmaster)
            ->post(route('admin.rooms.store'), [
                'school_id' => $school->id,
                'code' => 'RM01',
                'name' => 'Test',
                'building' => 'Gedung A',
                'floor' => '1',
                'capacity' => 40,
            ])
            ->assertForbidden();
    }

    public function test_toggle_status_activates_room_without_affecting_others(): void
    {
        $previous = Room::factory()->create(['is_active' => true]);
        $current = Room::factory()->create(['is_active' => false]);

        $this->patch(route('admin.rooms.toggle-status', $current))
            ->assertRedirect()
            ->assertSessionHas('toast.type', 'success');

        $this->assertTrue($previous->fresh()->is_active);
        $this->assertTrue($current->fresh()->is_active);
        $this->assertSame(2, Room::query()->where('is_active', true)->count());
    }

    public function test_toggle_status_deactivates_active_room(): void
    {
        $room = Room::factory()->create(['is_active' => true]);

        $this->patch(route('admin.rooms.toggle-status', $room))
            ->assertRedirect();

        $this->assertFalse($room->fresh()->is_active);
        $this->assertSame(0, Room::query()->where('is_active', true)->count());
    }

    public function test_update_validates_and_updates_room(): void
    {
        $room = Room::factory()->create(['code' => 'RM01', 'name' => 'Ruangan 1']);

        $this->put(route('admin.rooms.update', $room), [
            'code' => 'RM02',
            'name' => 'Ruangan 2',
            'building' => 'Gedung B',
            'floor' => '2',
            'capacity' => 35,
        ])->assertRedirect();

        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'code' => 'RM02',
            'name' => 'Ruangan 2',
            'building' => 'Gedung B',
            'floor' => '2',
            'capacity' => 35,
        ]);
    }

    public function test_update_validates_code_unique_within_the_school(): void
    {
        Room::factory()->create([
            'school_id' => $this->school->id,
            'code' => 'RM02',
            'is_active' => true,
        ]);

        $room = Room::factory()->create([
            'school_id' => $this->school->id,
            'code' => 'RM01',
            'name' => 'RM01',
        ]);

        $this->put(route('admin.rooms.update', $room), [
            'code' => 'RM02',
            'name' => 'Updated',
            'building' => 'Gedung A',
            'floor' => '1',
            'capacity' => 40,
        ])->assertSessionHasErrors('code');
    }

    public function test_destroy_deletes_room(): void
    {
        $room = Room::factory()->create();

        $this->delete(route('admin.rooms.destroy', $room))
            ->assertRedirect();

        $this->assertDatabaseMissing('rooms', [
            'id' => $room->id,
        ]);
    }
}
