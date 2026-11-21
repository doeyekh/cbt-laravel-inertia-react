<?php

namespace App\Http\Controllers\Admin;

use App\Enums\QuestionType;
use App\Http\Controllers\Admin\Concerns\AuthorizesQuestionAccess;
use App\Http\Controllers\Admin\Concerns\PresentsQuestionBank;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportQuestionsRequest;
use App\Imports\QuestionsImport;
use App\Models\QuestionBank;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class QuestionImportController extends Controller
{
    use AuthorizesQuestionAccess;
    use PresentsQuestionBank;

    /**
     * Display the question import page of the given question bank.
     */
    public function index(Request $request, QuestionBank $questionBank): Response
    {
        $this->authorizeQuestionAccess($request->user());
        $this->authorizeRecordAccess($request->user(), $questionBank);

        return Inertia::render('Questions/Import', [
            'title' => 'Import Soal '.$questionBank->name,
            'questionBank' => $this->presentQuestionBank($questionBank),
            'types' => $this->typeOptions(),
        ]);
    }

    /**
     * Import the questions of the given question bank from a spreadsheet.
     */
    public function store(ImportQuestionsRequest $request, QuestionBank $questionBank): RedirectResponse
    {
        $this->authorizeQuestionManage($request->user());
        $this->authorizeQuestionAccess($request->user());
        $this->authorizeRecordAccess($request->user(), $questionBank);

        $import = new QuestionsImport($questionBank, $request->user()?->id);

        try {
            Excel::import($import, $request->file('file'));
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Import Gagal',
                'description' => 'Berkas tidak dapat dibaca. Pastikan format dan isi berkas sudah benar.',
            ]);
        }

        if ($import->imported === 0) {
            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Tidak Ada Data Diimpor',
                'description' => $import->errors[0] ?? 'Berkas tidak memuat data soal.',
            ]);
        }

        $description = "{$import->imported} soal berhasil diimpor.";

        if ($import->skipped > 0) {
            $description .= " {$import->skipped} baris dilewati. ".$import->errors[0];
        }

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Import Selesai',
            'description' => $description,
        ]);
    }

    /**
     * Download the spreadsheet template of the question import.
     */
    public function template(Request $request, QuestionBank $questionBank): StreamedResponse
    {
        $this->authorizeQuestionAccess($request->user());
        $this->authorizeRecordAccess($request->user(), $questionBank);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Soal');

        $sheet->fromArray([$this->templateHeaders()], null, 'A1');
        $sheet->fromArray($this->templateRows($questionBank), null, 'A2');

        foreach (range('A', 'K') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $sheet->getStyle('1:1')->getFont()->setBold(true);

        $writer = new Xlsx($spreadsheet);
        $filename = 'template-import-soal-'.$questionBank->code.'.xlsx';

        return response()->streamDownload(
            fn () => $writer->save('php://output'),
            $filename,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]
        );
    }

    /**
     * Get the header row of the question import template.
     *
     * @return list<string>
     */
    private function templateHeaders(): array
    {
        return [
            'tipe',
            'nomor',
            'soal',
            'opsi_a',
            'opsi_b',
            'opsi_c',
            'opsi_d',
            'opsi_e',
            'kunci',
            'pasangan',
            'isian',
        ];
    }

    /**
     * Get the sample rows of the question import template.
     *
     * @return list<list<string>>
     */
    private function templateRows(QuestionBank $questionBank): array
    {
        $rows = [
            [
                'pilihan_ganda',
                '1',
                'Contoh soal pilihan ganda.',
                'Jawaban A',
                'Jawaban B',
                'Jawaban C',
                'Jawaban D',
                '',
                'A',
                '',
                '',
            ],
            [
                'pilihan_ganda_kompleks',
                '1',
                'Contoh soal pilihan ganda kompleks.',
                'Jawaban A',
                'Jawaban B',
                'Jawaban C',
                'Jawaban D',
                '',
                'A,C',
                '',
                '',
            ],
            [
                'menjodohkan',
                '1',
                'Contoh soal menjodohkan.',
                '',
                '',
                '',
                '',
                '',
                '',
                'Jantung=Memompa darah; Ginjal=Menyaring darah',
                '',
            ],
            [
                'isian_singkat',
                '1',
                'Contoh soal isian singkat.',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                'klorofil; Klorofil',
            ],
            [
                'esai',
                '1',
                'Contoh soal esai.',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
            ],
        ];

        $optionCount = (int) $questionBank->pilihan_ganda_option_count;

        if ($optionCount < 5) {
            foreach ([0, 1] as $index) {
                $rows[$index][3 + $optionCount] = '';
            }
        }

        return $rows;
    }

    /**
     * Get the question type options used by the import form.
     *
     * @return array<int, array{value: string, label: string, count_field: string}>
     */
    private function typeOptions(): array
    {
        return array_map(
            fn (QuestionType $type): array => [
                'value' => $type->value,
                'label' => $type->label(),
                'count_field' => $type->countColumn(),
            ],
            QuestionType::cases(),
        );
    }
}
