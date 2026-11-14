<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_access_user_management(): void
    {
        $this->actingAs(User::factory()->administrator()->create());

        $this->get(route('admin.users.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.user.role', 'administrator')
            );
    }

    #[DataProvider('userManagementEndpoints')]
    public function test_guru_cannot_access_user_management(string $method, string $routeName, array $parameters): void
    {
        $this->actingAs(User::factory()->guru()->create());
        $target = User::factory()->create();

        $url = route($routeName, array_fill_keys($parameters, $target->id));

        $this->{$method}($url)
            ->assertForbidden();
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: list<string>}>
     */
    public static function userManagementEndpoints(): array
    {
        return [
            'index' => ['get', 'admin.users.index', []],
            'store' => ['post', 'admin.users.store', []],
            'import' => ['post', 'admin.users.import', []],
            'toggle status' => ['patch', 'admin.users.toggle-status', ['userId']],
            'update' => ['put', 'admin.users.update', ['user']],
            'destroy' => ['delete', 'admin.users.destroy', ['user']],
        ];
    }
}
