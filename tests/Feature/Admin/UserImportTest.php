<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->administrator()->create());
    }

    public function test_guest_cannot_import_users(): void
    {
        auth()->logout();

        $this->post(route('admin.users.import'), [])
            ->assertRedirect(route('admin.login'));
    }

    public function test_import_requires_a_file(): void
    {
        $this->post(route('admin.users.import'), [])
            ->assertSessionHasErrors([
                'file' => 'Berkas import wajib dipilih.',
            ]);
    }

    public function test_import_rejects_unsupported_file_extension(): void
    {
        $file = UploadedFile::fake()->create('users.pdf', 10);

        $this->post(route('admin.users.import'), ['file' => $file])
            ->assertSessionHasErrors([
                'file' => 'Berkas harus berformat XLSX, XLS, atau CSV.',
            ]);
    }

    public function test_import_creates_users_with_default_password_and_guru_role(): void
    {
        $file = $this->csvFile(<<<'CSV'
name,username,email
Budi Santoso,budi,budi@example.com
Siti Aminah,siti,siti@example.com
CSV);

        $this->post(route('admin.users.import'), ['file' => $file])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $user = User::where('username', 'budi')->firstOrFail();

        $this->assertSame('Budi Santoso', $user->name);
        $this->assertSame('budi@example.com', $user->email);
        $this->assertSame(UserRole::Guru, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertTrue(Hash::check('password', $user->password));
        $this->assertDatabaseHas('users', ['username' => 'siti']);
    }

    public function test_import_uses_role_and_password_from_file_when_provided(): void
    {
        $file = $this->csvFile(<<<'CSV'
name,username,email,role,password
Admin Baru,adminbaru,adminbaru@example.com,administrator,rahasia123
CSV);

        $this->post(route('admin.users.import'), ['file' => $file])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $user = User::where('username', 'adminbaru')->firstOrFail();

        $this->assertSame(UserRole::Administrator, $user->role);
        $this->assertTrue(Hash::check('rahasia123', $user->password));
    }

    public function test_import_skips_invalid_and_duplicate_rows(): void
    {
        User::factory()->create([
            'username' => 'budi',
            'email' => 'budi@example.com',
        ]);

        $file = $this->csvFile(<<<'CSV'
name,username,email,role
Budi Duplikat,budi,budi2@example.com,guru
Tanpa Email,tanpaemail,,guru
Role Salah,rolesalah,rolesalah@example.com,superadmin
Pengguna Valid,validuser,validuser@example.com,guru
CSV);

        $this->post(route('admin.users.import'), ['file' => $file])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['username' => 'validuser']);
        $this->assertDatabaseMissing('users', ['username' => 'tanpaemail']);
        $this->assertDatabaseMissing('users', ['username' => 'rolesalah']);
        $this->assertSame(3, User::count());
    }

    private function csvFile(string $contents): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('users.csv', $contents);
    }
}
