<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_headmaster_and_admin_are_linked_to_guru_users(): void
    {
        $headmaster = User::factory()->guru()->create();
        $admin = User::factory()->guru()->create();
        $school = School::factory()->create([
            'headmaster_id' => $headmaster->id,
            'admin_id' => $admin->id,
        ]);

        $this->assertTrue($school->headmaster->is($headmaster));
        $this->assertTrue($school->admin->is($admin));
        $this->assertSame(UserRole::Guru, $school->headmaster->role);
        $this->assertSame(UserRole::Guru, $school->admin->role);
        $this->assertTrue($headmaster->headmasterOf->is($school));
        $this->assertTrue($admin->adminOf->is($school));
    }

    public function test_users_relation_only_includes_members_of_the_school(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();

        $administrator = User::factory()->administrator()->create(['school_id' => $school->id]);
        $guru = User::factory()->guru()->create(['school_id' => $school->id]);
        User::factory()->guru()->create(['school_id' => $otherSchool->id]);

        $this->assertEqualsCanonicalizing(
            [$administrator->id, $guru->id],
            $school->users()->pluck('id')->all()
        );
    }

    public function test_administrators_and_gurus_relations_filter_users_by_role(): void
    {
        $school = School::factory()->create();
        $administrator = User::factory()->administrator()->create(['school_id' => $school->id]);
        $guru = User::factory()->guru()->create(['school_id' => $school->id]);

        $this->assertSame([$administrator->id], $school->administrators()->pluck('id')->all());
        $this->assertSame([$guru->id], $school->gurus()->pluck('id')->all());
    }

    public function test_deleting_a_school_does_not_delete_its_users(): void
    {
        $school = School::factory()->create();
        $guru = User::factory()->guru()->create(['school_id' => $school->id]);

        $school->delete();

        $this->assertSoftDeleted($school);
        $this->assertNotSoftDeleted($guru);
        $this->assertSame($school->id, $guru->fresh()->school_id);
    }
}
