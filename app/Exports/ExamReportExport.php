<?php

namespace App\Exports;

use App\Models\ExamAttempt;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithFreezePane;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExamReportExport implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithFreezePane, WithHeadings, WithMapping, WithStyles, WithTitle
{
    /**
     * @param  Collection<int, ExamAttempt>  $attempts
     */
    public function __construct(private Collection $attempts) {}

    /**
     * @return Collection<int, ExamAttempt>
     */
    public function collection(): Collection
    {
        return $this->attempts;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Nomor Peserta',
            'Nama',
            'NISN',
            'Status',
            'Jumlah Benar',
            'Jumlah Salah',
            'Belum Dinilai',
            'Skor',
            'Skor Maksimum',
            'Nilai Akhir (0-100)',
            'Waktu (detik)',
            'Dikumpulkan Pada',
        ];
    }

    /**
     * @param  ExamAttempt  $attempt
     * @return array<int, mixed>
     */
    public function map(mixed $attempt): array
    {
        $answers = $attempt->answers;

        return [
            $attempt->participant?->participant_number,
            $attempt->participant?->student?->name,
            $attempt->participant?->student?->nisn,
            $attempt->status->label(),
            $answers->whereStrict('is_correct', true)->count(),
            $answers->whereStrict('is_correct', false)->count(),
            $answers->whereStrict('is_correct', null)->count(),
            $attempt->score === null ? null : (float) $attempt->score,
            $attempt->max_score === null ? null : (float) $attempt->max_score,
            $attempt->scorePercentage(),
            $attempt->time_spent_seconds,
            $attempt->submitted_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
            'C' => NumberFormat::FORMAT_TEXT,
            'E' => NumberFormat::FORMAT_NUMBER,
            'F' => NumberFormat::FORMAT_NUMBER,
            'G' => NumberFormat::FORMAT_NUMBER,
            'H' => NumberFormat::FORMAT_NUMBER_00,
            'I' => NumberFormat::FORMAT_NUMBER_00,
            'J' => NumberFormat::FORMAT_NUMBER_00,
            'K' => NumberFormat::FORMAT_NUMBER,
        ];
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
        return 'Laporan Ujian';
    }
}
