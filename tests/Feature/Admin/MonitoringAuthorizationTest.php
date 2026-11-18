<?php

namespace Tests\Feature\Admin;

use App\Models\ExamAttempt;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MonitoringAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get(route('admin.monitoring.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_school_admin_can_access_monitoring(): void
    {
        $schoolAdmin = User::factory()->guru()->create();
        School::factory()->create(['admin_id' => $schoolAdmin->id]);

        $this->actingAs($schoolAdmin)
            ->get(route('admin.monitoring.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Monitoring/Index')
                ->where('title', 'Monitoring Ujian')
            );
    }

    public function test_platform_administrator_cannot_access_monitoring(): void
    {
        $this->actingAs(User::factory()->administrator()->create())
            ->get(route('admin.monitoring.index'))
            ->assertForbidden();
    }

    public function test_headmaster_cannot_access_monitoring(): void
    {
        $headmaster = User::factory()->guru()->create();
        School::factory()->create(['headmaster_id' => $headmaster->id]);

        $this->actingAs($headmaster)
            ->get(route('admin.monitoring.index'))
            ->assertForbidden();
    }

    public function test_regular_guru_cannot_access_monitoring(): void
    {
        $this->actingAs(User::factory()->guru()->create())
            ->get(route('admin.monitoring.index'))
            ->assertForbidden();
    }

    public function test_school_admin_cannot_reset_an_attempt_from_another_school(): void
    {
        $attempt = ExamAttempt::factory()->create();
        $schoolAdmin = User::factory()->guru()->create();
        School::factory()->create(['admin_id' => $schoolAdmin->id]);

        $this->actingAs($schoolAdmin)
            ->delete(route('admin.exam-attempts.reset', $attempt))
            ->assertNotFound();

        $this->assertDatabaseHas('exam_attempts', ['id' => $attempt->id]);
    }
}
