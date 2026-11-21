<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GuruAccessTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('allowedMenus')]
    public function test_guru_can_access_allowed_menu(string $routeName, string $component): void
    {
        $this->actingAs(User::factory()->guru()->create());

        $this->get(route($routeName))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component($component));
    }

    #[DataProvider('forbiddenEndpoints')]
    public function test_guru_cannot_access_forbidden_admin_endpoint(string $method, string $routeName): void
    {
        $this->actingAs(User::factory()->guru()->create());

        if (in_array($method, ['post', 'put', 'patch', 'delete'], true)) {
            $this->withoutMiddleware(PreventRequestForgery::class);
        }

        $this->{$method}(route($routeName))
            ->assertForbidden();
    }

    #[DataProvider('accountRoutes')]
    public function test_guru_can_manage_own_account(string $routeName): void
    {
        $this->actingAs(User::factory()->guru()->create());

        $this->get(route($routeName))
            ->assertOk();
    }

    public function test_guru_cannot_access_the_dashboard(): void
    {
        $this->actingAs(User::factory()->guru()->create());

        $this->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_administrator_keeps_access_to_user_management(): void
    {
        $this->actingAs(User::factory()->administrator()->create());

        $this->get(route('admin.users.index'))
            ->assertOk();
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function allowedMenus(): array
    {
        return [
            'bank soal' => ['admin.question-banks.index', 'QuestionBanks/Index'],
            'laporan' => ['admin.reports.index', 'Reports/Index'],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function forbiddenEndpoints(): array
    {
        return [
            'monitoring' => ['get', 'admin.monitoring.index'],
            'students' => ['get', 'admin.students.index'],
            'exam packages' => ['get', 'admin.exam-packages.index'],
            'exam participants' => ['get', 'admin.exam-participants.index'],
            'sessions' => ['get', 'admin.sessions.index'],
            'rooms' => ['get', 'admin.rooms.index'],
            'classes' => ['get', 'admin.classes.index'],
            'schools' => ['get', 'admin.schools.index'],
            'subjects' => ['get', 'admin.subjects.index'],
            'exam types' => ['get', 'admin.exam-types.index'],
            'assessment types' => ['get', 'admin.assessment-types.index'],
            'academic years' => ['get', 'admin.academic-years.index'],
            'users' => ['get', 'admin.users.index'],
            'attendances' => ['get', 'admin.attendances.index'],
            'create student' => ['post', 'admin.students.store'],
            'create exam package' => ['post', 'admin.exam-packages.store'],
        ];
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function accountRoutes(): array
    {
        return [
            'profile' => ['admin.profile.show'],
            'password' => ['admin.password.edit'],
        ];
    }
}
