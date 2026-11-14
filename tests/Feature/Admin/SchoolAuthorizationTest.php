<?php

namespace Tests\Feature\Admin;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_school_management(): void
    {
        $this->get(route('admin.schools.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_guru_cannot_access_school_management(): void
    {
        $school = School::factory()->create();

        $this->actingAs(User::factory()->guru()->create());

        $this->get(route('admin.schools.index'))
            ->assertForbidden();

        $this->post(route('admin.schools.store'), [
            'code' => 'YAS-099',
            'name' => 'Sekolah Terlarang',
        ])->assertForbidden();

        $this->delete(route('admin.schools.destroy', $school))
            ->assertForbidden();
    }

    public function test_administrator_can_access_school_management(): void
    {
        $this->actingAs(User::factory()->administrator()->create());

        $this->get(route('admin.schools.index'))
            ->assertOk();
    }
}
