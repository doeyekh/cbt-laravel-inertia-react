<?php

namespace App\Imports;

use App\Enums\Gender;
use App\Enums\RegistrationStatus;
use App\Models\Student;
use App\Models\StudentRegistration;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

class StudentsImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    public int $imported = 0;

    public int $skipped = 0;

    /** @var list<string> */
    public array $errors = [];

    public function __construct(
        private readonly int $schoolId,
        private readonly int $entryAcademicYearId,
    ) {}

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
        $nisn = trim((string) $this->value($row, ['nisn']));
        $nis = strtoupper(trim((string) $this->value($row, ['nis', 'no_induk', 'nomor_induk'])));
        $name = trim((string) $this->value($row, ['nama', 'name', 'nama_lengkap']));
        $gender = $this->normalizeGender($this->value($row, ['jenis_kelamin', 'gender', 'jk']));
        $birthPlace = trim((string) $this->value($row, ['tempat_lahir', 'birth_place']));
        $birthDateRaw = $this->value($row, ['tanggal_lahir', 'birth_date']);
        $birthDate = $this->parseDate($birthDateRaw);
        $fatherName = trim((string) $this->value($row, ['nama_ayah', 'ayah', 'father_name']));
        $fatherOccupation = trim((string) $this->value($row, ['pekerjaan_ayah', 'father_occupation']));
        $fatherPhone = trim((string) $this->value($row, ['no_hp_ayah', 'hp_ayah', 'telepon_ayah', 'father_phone']));
        $motherName = trim((string) $this->value($row, ['nama_ibu', 'ibu', 'mother_name']));
        $motherOccupation = trim((string) $this->value($row, ['pekerjaan_ibu', 'mother_occupation']));
        $motherPhone = trim((string) $this->value($row, ['no_hp_ibu', 'hp_ibu', 'telepon_ibu', 'mother_phone']));
        $parentAddress = trim((string) $this->value($row, ['alamat_orang_tua', 'alamat', 'parent_address']));
        $parentBio = trim((string) $this->value($row, ['biografi_orang_tua', 'catatan_orang_tua', 'biografi', 'parent_bio']));

        $validator = Validator::make(
            [
                'nisn' => $nisn !== '' ? $nisn : null,
                'nis' => $nis,
                'name' => $name,
                'gender' => $gender,
                'birth_date' => $birthDate,
                'father_name' => $fatherName !== '' ? $fatherName : null,
                'father_occupation' => $fatherOccupation !== '' ? $fatherOccupation : null,
                'father_phone' => $fatherPhone !== '' ? $fatherPhone : null,
                'mother_name' => $motherName !== '' ? $motherName : null,
                'mother_occupation' => $motherOccupation !== '' ? $motherOccupation : null,
                'mother_phone' => $motherPhone !== '' ? $motherPhone : null,
                'parent_address' => $parentAddress !== '' ? $parentAddress : null,
                'parent_bio' => $parentBio !== '' ? $parentBio : null,
            ],
            [
                'nisn' => ['nullable', 'string', 'max:20', 'unique:students,nisn'],
                'nis' => [
                    'required',
                    'string',
                    'max:30',
                    Rule::unique('student_registrations', 'nis')
                        ->where('school_id', $this->schoolId),
                ],
                'name' => ['required', 'string', 'max:255'],
                'gender' => ['required', Rule::in([Gender::LakiLaki->value, Gender::Perempuan->value])],
                'birth_date' => ['nullable', 'date'],
                'father_name' => ['nullable', 'string', 'max:255'],
                'father_occupation' => ['nullable', 'string', 'max:255'],
                'father_phone' => ['nullable', 'string', 'max:30'],
                'mother_name' => ['nullable', 'string', 'max:255'],
                'mother_occupation' => ['nullable', 'string', 'max:255'],
                'mother_phone' => ['nullable', 'string', 'max:30'],
                'parent_address' => ['nullable', 'string', 'max:1000'],
                'parent_bio' => ['nullable', 'string', 'max:2000'],
            ],
            [
                'nisn.unique' => 'NISN sudah terdaftar pada siswa lain.',
                'nis.required' => 'NIS wajib diisi.',
                'nis.unique' => 'NIS sudah digunakan di sekolah ini.',
                'name.required' => 'Nama lengkap wajib diisi.',
                'gender.required' => 'Jenis kelamin wajib diisi (L/P).',
                'gender.in' => 'Jenis kelamin harus L atau P.',
                'birth_date.date' => 'Tanggal lahir tidak valid.',
            ]
        );

        if ($validator->fails()) {
            $this->skip($line, $validator->errors()->all());

            return;
        }

        try {
            DB::transaction(function () use (
                $nisn,
                $nis,
                $name,
                $gender,
                $birthPlace,
                $birthDate,
                $fatherName,
                $fatherOccupation,
                $fatherPhone,
                $motherName,
                $motherOccupation,
                $motherPhone,
                $parentAddress,
                $parentBio
            ): void {
                $student = Student::create([
                    'nisn' => $nisn !== '' ? $nisn : null,
                    'name' => $name,
                    'gender' => $gender,
                    'birth_place' => $birthPlace !== '' ? $birthPlace : null,
                    'birth_date' => $birthDate,
                    'father_name' => $fatherName !== '' ? $fatherName : null,
                    'father_occupation' => $fatherOccupation !== '' ? $fatherOccupation : null,
                    'father_phone' => $fatherPhone !== '' ? $fatherPhone : null,
                    'mother_name' => $motherName !== '' ? $motherName : null,
                    'mother_occupation' => $motherOccupation !== '' ? $motherOccupation : null,
                    'mother_phone' => $motherPhone !== '' ? $motherPhone : null,
                    'parent_address' => $parentAddress !== '' ? $parentAddress : null,
                    'parent_bio' => $parentBio !== '' ? $parentBio : null,
                ]);

                StudentRegistration::create([
                    'student_id' => $student->id,
                    'school_id' => $this->schoolId,
                    'nis' => $nis,
                    'entry_academic_year_id' => $this->entryAcademicYearId,
                    'status' => RegistrationStatus::Aktif,
                    'registered_at' => now()->toDateString(),
                ]);
            });
        } catch (Throwable $exception) {
            report($exception);

            $this->skip($line, ['Baris tidak dapat disimpan.']);

            return;
        }

        $this->imported++;
    }

    /**
     * Normalize various gender inputs to the enum values.
     */
    private function normalizeGender(mixed $value): ?string
    {
        $gender = mb_strtolower(trim((string) $value));

        return match ($gender) {
            'l', 'lk', 'laki-laki', 'laki laki', 'male', 'm' => Gender::LakiLaki->value,
            'p', 'pr', 'perempuan', 'female', 'f' => Gender::Perempuan->value,
            default => null,
        };
    }

    /**
     * Parse an Excel serial number or a textual date into a Y-m-d string.
     */
    private function parseDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            }

            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (Throwable) {
            return (string) $value;
        }
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
