<?php

namespace App\Imports;

use App\Models\Subject;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SubjectsImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    public int $imported = 0;

    public int $skipped = 0;

    /** @var list<string> */
    public array $errors = [];

    /** @var array<string, bool> */
    private array $seenCodes = [];

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
        $code = mb_strtoupper(trim((string) $this->value($row, ['code', 'kode', 'kode_mapel', 'kode_mata_pelajaran'])));
        $name = trim((string) $this->value($row, ['name', 'nama', 'nama_mapel', 'nama_mata_pelajaran']));
        $rawStatus = mb_strtolower(trim((string) $this->value($row, ['status', 'is_active', 'aktif'])));

        $isActive = in_array($rawStatus, ['1', 'true', 'aktif', 'active', 'ya'], true);

        if ($code !== '' && isset($this->seenCodes[$code])) {
            $this->skip($line, ["Kode \"{$code}\" duplikat dalam berkas import."]);

            return;
        }

        $validator = Validator::make(
            ['code' => $code, 'name' => $name],
            [
                'code' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9-]+$/', 'unique:subjects,code'],
                'name' => ['required', 'string', 'max:100'],
            ],
            [
                'code.required' => 'Kode mata pelajaran wajib diisi.',
                'code.max' => 'Kode maksimal 20 karakter.',
                'code.regex' => 'Kode hanya boleh berisi huruf kapital, angka, dan tanda hubung.',
                'code.unique' => "Kode \"{$code}\" sudah terdaftar di sistem.",
                'name.required' => 'Nama mata pelajaran wajib diisi.',
                'name.max' => 'Nama mata pelajaran maksimal 100 karakter.',
            ]
        );

        if ($validator->fails()) {
            $this->skip($line, $validator->errors()->all());

            return;
        }

        $this->seenCodes[$code] = true;

        Subject::create([
            'code' => $code,
            'name' => $name,
            'is_active' => $isActive,
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
