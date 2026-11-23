<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BackupControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_view_backup_page(): void
    {
        Storage::fake('backups');

        $this->actingAs(User::factory()->administrator()->create());

        $this->get(route('admin.backups.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backups/Index')
                ->where('backups', [])
                ->has('types', 2)
                ->has('settings')
            );
    }

    #[DataProvider('backupEndpoints')]
    public function test_guru_cannot_access_backup_endpoints(string $method, string $routeName, array $parameters): void
    {
        $this->actingAs(User::factory()->guru()->create());

        $this->{$method}(route($routeName, $parameters))
            ->assertForbidden();
    }

    public function test_restore_requires_password_and_confirmation(): void
    {
        $this->actingAs(User::factory()->administrator()->create());

        $this->post(route('admin.backups.restore', ['backup' => 'dummy.zip']), [])
            ->assertSessionHasErrors(['current_password', 'confirmation']);
    }

    public function test_restore_rejects_incorrect_current_password(): void
    {
        $user = User::factory()->administrator()->create([
            'password' => Hash::make('rahasia123'),
        ]);

        $this->actingAs($user)
            ->post(route('admin.backups.restore', ['backup' => 'dummy.zip']), [
                'current_password' => 'salah',
                'confirmation' => 'RESTORE',
            ])
            ->assertSessionHasErrors('current_password');
    }

    #[DataProvider('invalidBackupTypes')]
    public function test_store_rejects_invalid_backup_type(array $payload): void
    {
        $this->actingAs(User::factory()->administrator()->create());

        $this->post(route('admin.backups.store'), $payload)
            ->assertSessionHasErrors('type');
    }

    /**
     * @return array<string, array{0: array<string, string>}>
     */
    public static function invalidBackupTypes(): array
    {
        return [
            'missing' => [[]],
            'unknown' => [['type' => 'files-only']],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: array<string, string>}>
     */
    public static function backupEndpoints(): array
    {
        return [
            'index' => ['get', 'admin.backups.index', []],
            'store' => ['post', 'admin.backups.store', []],
            'download' => ['get', 'admin.backups.download', ['backup' => 'dummy.zip']],
            'restore' => ['post', 'admin.backups.restore', ['backup' => 'dummy.zip']],
            'destroy' => ['delete', 'admin.backups.destroy', ['backup' => 'dummy.zip']],
        ];
    }
}
