<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithFreezePane;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LegerGradeExport implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithFreezePane, WithHeadings, WithMapping, WithStyles, WithTitle
{
    private int $sequence = 0;

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @param  Collection<int, array<string, mixed>>  $columns
     */
    public function __construct(
        private Collection $rows,
        private Collection $columns,
    ) {}

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function collection(): Collection
    {
        return $this->rows;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return array_merge(
            [
                'No',
                'Nomor Peserta',
                'NISN',
                'Nama Siswa',
                'Kelas',
                'Sekolah',
            ],
            $this->columns->pluck('name')->all(),
            [
                'Rata-rata',
                'Jumlah Ujian',
                'Tuntas',
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<int, mixed>
     */
    public function map(mixed $row): array
    {
        $this->sequence++;

        $subjectValues = $this->columns
            ->map(fn (array $column): mixed => $row['scores'][(string) $column['id']] ?? null)
            ->all();

        return array_merge(
            [
                $this->sequence,
                $row['participant_number'] ?? null,
                $row['nisn'] ?? null,
                $row['name'] ?? null,
                $row['class'] ?? null,
                $row['school'] ?? null,
            ],
            $subjectValues,
            [
                $row['average'] ?? null,
                $row['completed_count'] ?? 0,
                $row['passed_count'] ?? 0,
            ],
        );
    }

    /**
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        $formats = [
            'B' => NumberFormat::FORMAT_TEXT,
            'C' => NumberFormat::FORMAT_TEXT,
        ];

        $index = 7; // Kolom G = mapel pertama.

        foreach ($this->columns as $column) {
            $formats[Coordinate::stringFromColumnIndex($index)] = NumberFormat::FORMAT_NUMBER_00;
            $index++;
        }

        $formats[Coordinate::stringFromColumnIndex($index)] = NumberFormat::FORMAT_NUMBER_00;
        $index++;
        $formats[Coordinate::stringFromColumnIndex($index)] = NumberFormat::FORMAT_NUMBER;
        $index++;
        $formats[Coordinate::stringFromColumnIndex($index)] = NumberFormat::FORMAT_NUMBER;

        return $formats;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function freezePane(): string
    {
        return 'A2';
    }

    public function title(): string
    {
        return 'Leger Nilai';
    }
}
