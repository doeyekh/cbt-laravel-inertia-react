<?php

namespace App\Imports;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UsersImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    public const DEFAULT_PASSWORD = 'password';

    public const DEFAULT_ROLE = 'guru';

    public int $imported = 0;

    public int $skipped = 0;

    /** @var list<string> */
    public array $errors = [];

    /**
     * Import each row of the uploaded spreadsheet.
     *
     * @param  Collection<int, Collection<string, mixed>>  $rows
     */
    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $this->importRow($row->toArray(), $index + 2);
        }
    }

    /**
     * Validate and persist a single spreadsheet row.
     *
     * @param  array<string, mixed>  $row
     */
    private function importRow(array $row, int $line): void
    {
        $name = trim((string) $this->value($row, ['name', 'nama', 'nama_lengkap']));
        $username = trim((string) $this->value($row, ['username', 'nama_pengguna', 'user_name']));
        $email = trim((string) $this->value($row, ['email', 'surel']));
        $role = mb_strtolower(trim((string) $this->value($row, ['role', 'peran'])));
        $password = (string) $this->value($row, ['password', 'kata_sandi', 'sandi']);

        $validator = Validator::make(
            ['name' => $name, 'username' => $username, 'email' => $email],
            [
                'name' => ['required', 'string', 'max:255'],
                'username' => ['required', 'string', 'max:50', 'unique:users,username'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            ],
            [
                'name.required' => 'Nama lengkap wajib diisi.',
                'username.required' => 'Username wajib diisi.',
                'username.unique' => 'Username sudah digunakan.',
                'email.required' => 'Email wajib diisi.',
                'email.email' => 'Format email tidak valid.',
                'email.unique' => 'Email sudah digunakan.',
            ]
        );

        if ($validator->fails()) {
            $this->skip($line, $validator->errors()->all());

            return;
        }

        if ($password !== '' && mb_strlen($password) < 8) {
            $this->skip($line, ['Kata sandi minimal 8 karakter.']);

            return;
        }

        if ($role !== '' && UserRole::tryFrom($role) === null) {
            $this->skip($line, ['Role harus salah satu dari: administrator, guru.']);

            return;
        }

        User::create([
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'role' => $role !== '' ? $role : self::DEFAULT_ROLE,
            'password' => $password !== '' ? $password : self::DEFAULT_PASSWORD,
            'is_active' => true,
        ]);

        $this->imported++;
    }

    /**
     * Record a row that could not be imported.
     *
     * @param  list<string>  $messages
     */
    private function skip(int $line, array $messages): void
    {
        $this->skipped++;
        $this->errors[] = "Baris {$line}: ".implode(' ', $messages);
    }

    /**
     * Read the first matching heading value from a row.
     *
     * @param  array<string, mixed>  $row
     * @param  list<string>  $keys
     */
    private function value(array $row, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row) && $row[$key] !== null) {
                return $row[$key];
            }
        }

        return null;
    }
}
