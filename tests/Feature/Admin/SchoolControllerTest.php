<?php

namespace Tests\Feature\Admin;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SchoolControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->administrator()->create());
    }

    public function test_index_returns_paginated_schools(): void
    {
        School::factory()->count(7)->create();

        $this->get(route('admin.schools.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Schools/Index')
                ->has('schools.data', 5)
                ->where('schools.total', 7)
                ->where('filters.search', '')
                ->where('stats.total', 7)
            );
    }

    public function test_index_filters_by_name_code_or_headmaster_name(): void
    {
        $headmaster = User::factory()->guru()->create(['name' => 'Budi Santoso']);
        $otherHeadmaster = User::factory()->guru()->create(['name' => 'Siti Rahmawati']);

        School::factory()->create([
            'code' => 'YAS-001',
            'name' => 'SMA Nusantara',
            'headmaster_id' => $headmaster->id,
        ]);
        School::factory()->create([
            'code' => 'YAS-002',
            'name' => 'SMP Nusantara',
            'headmaster_id' => $otherHeadmaster->id,
        ]);

        $this->get(route('admin.schools.index', ['search' => 'Budi']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('schools.data', 1)
                ->where('schools.data.0.code', 'YAS-001')
            );
    }

    public function test_index_includes_school_stats_and_guru_teacher_options(): void
    {
        $teacher = User::factory()->guru()->create();
        School::factory()->active()->create();
        School::factory()->inactive()->count(2)->create();

        $this->get(route('admin.schools.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.total', 3)
                ->where('stats.active', 1)
                ->where('stats.inactive', 2)
                ->has('teachers', 1)
                ->where('teachers.0.id', $teacher->id)
                ->where('teachers.0.name', $teacher->name)
            );
    }

    public function test_store_creates_inactive_school_with_headmaster_and_admin(): void
    {
        $headmaster = User::factory()->guru()->create();
        $schoolAdmin = User::factory()->guru()->create();

        $this->post(route('admin.schools.store'), [
            'code' => 'YAS-003',
            'name' => 'SMK Nusantara',
            'headmaster_id' => $headmaster->id,
            'admin_id' => $schoolAdmin->id,
            'address' => 'Jl. Mawar No. 2',
        ])->assertRedirect();

        $this->assertDatabaseHas('schools', [
            'code' => 'YAS-003',
            'name' => 'SMK Nusantara',
            'headmaster_id' => $headmaster->id,
            'admin_id' => $schoolAdmin->id,
            'address' => 'Jl. Mawar No. 2',
            'is_active' => false,
        ]);
    }

    public function test_store_requires_code_and_name(): void
    {
        $this->post(route('admin.schools.store'), [])
            ->assertSessionHasErrors(['code', 'name']);
    }

    public function test_store_rejects_duplicate_code(): void
    {
        School::factory()->create(['code' => 'YAS-001']);

        $this->post(route('admin.schools.store'), [
            'code' => 'YAS-001',
            'name' => 'Sekolah Baru',
        ])->assertSessionHasErrors('code');
    }

    public function test_store_rejects_lowercase_code(): void
    {
        $this->post(route('admin.schools.store'), [
            'code' => 'yas-001',
            'name' => 'Sekolah Baru',
        ])->assertSessionHasErrors('code');
    }

    #[DataProvider('guruRoleFields')]
    public function test_store_rejects_user_with_administrator_role(string $field): void
    {
        $administrator = User::factory()->administrator()->create();

        $this->post(route('admin.schools.store'), [
            'code' => 'YAS-004',
            'name' => 'Sekolah Baru',
            $field => $administrator->id,
        ])->assertSessionHasErrors($field);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function guruRoleFields(): array
    {
        return [
            'headmaster' => ['headmaster_id'],
            'admin' => ['admin_id'],
        ];
    }

    public function test_update_changes_school_fields_without_changing_its_own_code(): void
    {
        $headmaster = User::factory()->guru()->create();
        $schoolAdmin = User::factory()->guru()->create();
        $school = School::factory()->create([
            'code' => 'YAS-001',
            'name' => 'Nama Lama',
        ]);

        $this->put(route('admin.schools.update', $school), [
            'code' => 'YAS-001',
            'name' => 'Nama Baru',
            'headmaster_id' => $headmaster->id,
            'admin_id' => $schoolAdmin->id,
            'address' => 'Alamat Baru',
        ])->assertRedirect();

        $this->assertDatabaseHas('schools', [
            'id' => $school->id,
            'code' => 'YAS-001',
            'name' => 'Nama Baru',
            'headmaster_id' => $headmaster->id,
            'admin_id' => $schoolAdmin->id,
            'address' => 'Alamat Baru',
        ]);
    }

    public function test_update_can_clear_headmaster_and_admin(): void
    {
        $headmaster = User::factory()->guru()->create();
        $schoolAdmin = User::factory()->guru()->create();
        $school = School::factory()->create([
            'headmaster_id' => $headmaster->id,
            'admin_id' => $schoolAdmin->id,
        ]);

        $this->put(route('admin.schools.update', $school), [
            'headmaster_id' => null,
            'admin_id' => null,
        ])->assertRedirect();

        $this->assertDatabaseHas('schools', [
            'id' => $school->id,
            'headmaster_id' => null,
            'admin_id' => null,
        ]);
    }

    public function test_update_rejects_code_used_by_another_school(): void
    {
        School::factory()->create(['code' => 'YAS-001']);
        $school = School::factory()->create(['code' => 'YAS-002']);

        $this->put(route('admin.schools.update', $school), [
            'code' => 'YAS-001',
        ])->assertSessionHasErrors('code');
    }

    public function test_toggle_status_activates_school(): void
    {
        $school = School::factory()->inactive()->create();

        $this->patch(route('admin.schools.toggle-status', $school))
            ->assertRedirect();

        $this->assertTrue($school->fresh()->is_active);
    }

    public function test_toggle_status_deactivates_school(): void
    {
        $school = School::factory()->active()->create();

        $this->patch(route('admin.schools.toggle-status', $school))
            ->assertRedirect();

        $this->assertFalse($school->fresh()->is_active);
    }

    public function test_destroy_soft_deletes_inactive_school(): void
    {
        $school = School::factory()->inactive()->create();

        $this->delete(route('admin.schools.destroy', $school))
            ->assertRedirect();

        $this->assertSoftDeleted($school);
    }

    public function test_destroy_rejects_active_school(): void
    {
        $school = School::factory()->active()->create();

        $this->delete(route('admin.schools.destroy', $school))
            ->assertRedirect();

        $this->assertNotSoftDeleted($school);
    }

    public function test_kepala_sekolah_can_only_see_their_own_school(): void
    {
        $headmaster = User::factory()->guru()->create();
        $otherHeadmaster = User::factory()->guru()->create();

        $mySchool = School::factory()->create([
            'name' => 'Sekolah Saya',
            'headmaster_id' => $headmaster->id,
        ]);
        School::factory()->create([
            'name' => 'Sekolah Lain',
            'headmaster_id' => $otherHeadmaster->id,
        ]);

        $this->actingAs($headmaster)
            ->get(route('admin.schools.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Schools/Index')
                ->has('schools.data', 1)
                ->where('schools.data.0.id', $mySchool->id)
                ->where('schools.data.0.name', 'Sekolah Saya')
                ->where('schools.data.0.can_edit', false)
                ->where('schools.data.0.can_delete', false)
                ->where('schools.data.0.can_toggle_status', false)
                ->where('permissions.can_create', false)
                ->where('stats.total', 1)
            );
    }

    public function test_kepala_sekolah_cannot_create_school(): void
    {
        $headmaster = User::factory()->guru()->create();

        $this->actingAs($headmaster)
            ->post(route('admin.schools.store'), [
                'code' => 'YAS-NEW',
                'name' => 'Sekolah Baru',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('schools', ['code' => 'YAS-NEW']);
    }

    public function test_kepala_sekolah_cannot_update_their_school(): void
    {
        $headmaster = User::factory()->guru()->create();
        $school = School::factory()->create([
            'name' => 'Nama Asli',
            'headmaster_id' => $headmaster->id,
        ]);

        $this->actingAs($headmaster)
            ->put(route('admin.schools.update', $school), [
                'name' => 'Nama Diubah',
            ])
            ->assertForbidden();

        $this->assertSame('Nama Asli', $school->fresh()->name);
    }

    public function test_kepala_sekolah_cannot_toggle_status_or_delete_school(): void
    {
        $headmaster = User::factory()->guru()->create();
        $school = School::factory()->inactive()->create([
            'headmaster_id' => $headmaster->id,
        ]);

        $this->actingAs($headmaster)
            ->patch(route('admin.schools.toggle-status', $school))
            ->assertForbidden();

        $this->actingAs($headmaster)
            ->delete(route('admin.schools.destroy', $school))
            ->assertForbidden();

        $this->assertNotSoftDeleted($school);
    }

    public function test_admin_sekolah_can_only_see_their_own_school(): void
    {
        $schoolAdmin = User::factory()->guru()->create();
        $otherAdmin = User::factory()->guru()->create();

        $mySchool = School::factory()->create([
            'name' => 'Sekolah Binaan',
            'admin_id' => $schoolAdmin->id,
        ]);
        School::factory()->create([
            'name' => 'Sekolah Lain',
            'admin_id' => $otherAdmin->id,
        ]);

        $this->actingAs($schoolAdmin)
            ->get(route('admin.schools.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Schools/Index')
                ->has('schools.data', 1)
                ->where('schools.data.0.id', $mySchool->id)
                ->where('schools.data.0.name', 'Sekolah Binaan')
                ->where('schools.data.0.can_edit', true)
                ->where('schools.data.0.can_delete', false)
                ->where('schools.data.0.can_toggle_status', false)
                ->where('permissions.can_create', false)
                ->where('stats.total', 1)
            );
    }

    public function test_admin_sekolah_can_update_their_own_school_name_and_address(): void
    {
        $schoolAdmin = User::factory()->guru()->create();
        $originalHeadmaster = User::factory()->guru()->create();
        $school = School::factory()->create([
            'code' => 'YAS-OLD',
            'name' => 'Nama Lama',
            'address' => 'Alamat Lama',
            'headmaster_id' => $originalHeadmaster->id,
            'admin_id' => $schoolAdmin->id,
        ]);

        $otherTeacher = User::factory()->guru()->create();

        $this->actingAs($schoolAdmin)
            ->put(route('admin.schools.update', $school), [
                'code' => 'YAS-TAMPERED',
                'name' => 'Nama Baru Oleh Admin Sekolah',
                'address' => 'Alamat Baru',
                'headmaster_id' => $otherTeacher->id,
                'admin_id' => $otherTeacher->id,
            ])
            ->assertRedirect();

        $fresh = $school->fresh();
        $this->assertSame('Nama Baru Oleh Admin Sekolah', $fresh->name);
        $this->assertSame('Alamat Baru', $fresh->address);
        // Code, headmaster_id, and admin_id must NOT be altered by school admin
        $this->assertSame('YAS-OLD', $fresh->code);
        $this->assertSame($originalHeadmaster->id, $fresh->headmaster_id);
        $this->assertSame($schoolAdmin->id, $fresh->admin_id);
    }

    public function test_admin_sekolah_cannot_update_other_school(): void
    {
        $schoolAdmin = User::factory()->guru()->create();
        $otherSchool = School::factory()->create([
            'name' => 'Sekolah Orang Lain',
            'admin_id' => null,
        ]);

        $this->actingAs($schoolAdmin)
            ->put(route('admin.schools.update', $otherSchool), [
                'name' => 'Hacked',
            ])
            ->assertForbidden();

        $this->assertSame('Sekolah Orang Lain', $otherSchool->fresh()->name);
    }

    public function test_admin_sekolah_cannot_create_toggle_status_or_delete_school(): void
    {
        $schoolAdmin = User::factory()->guru()->create();
        $mySchool = School::factory()->inactive()->create([
            'admin_id' => $schoolAdmin->id,
        ]);

        $this->actingAs($schoolAdmin)
            ->post(route('admin.schools.store'), [
                'code' => 'YAS-FAIL',
                'name' => 'Sekolah Ilegal',
            ])
            ->assertForbidden();

        $this->actingAs($schoolAdmin)
            ->patch(route('admin.schools.toggle-status', $mySchool))
            ->assertForbidden();

        $this->actingAs($schoolAdmin)
            ->delete(route('admin.schools.destroy', $mySchool))
            ->assertForbidden();

        $this->assertNotSoftDeleted($mySchool);
    }

    public function test_regular_guru_cannot_access_schools(): void
    {
        $regularGuru = User::factory()->guru()->create();
        School::factory()->create();

        $this->actingAs($regularGuru)
            ->get(route('admin.schools.index'))
            ->assertForbidden();
    }
}
