<?php

namespace App\Imports;

use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionBlank;
use App\Models\QuestionOption;
use App\Models\QuestionPair;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Throwable;

class QuestionsImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    public int $imported = 0;

    public int $skipped = 0;

    /** @var list<string> */
    public array $errors = [];

    public function __construct(
        private readonly QuestionBank $questionBank,
        private readonly ?int $createdBy = null,
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
        $type = $this->normalizeType($this->value($row, ['tipe', 'type', 'jenis']));

        if ($type === null) {
            $this->skip($line, ['Tipe soal tidak dikenali.']);

            return;
        }

        $requiredCount = (int) $this->questionBank->{$type->countColumn()};

        if ($requiredCount === 0) {
            $this->skip($line, ["Tipe {$type->label()} tidak termasuk dalam komposisi bank soal ini."]);

            return;
        }

        $number = (int) $this->value($row, ['nomor', 'number', 'no']);
        $body = trim((string) $this->value($row, ['soal', 'body', 'pertanyaan', 'question']));

        $validator = Validator::make(
            [
                'number' => $number > 0 ? $number : null,
                'body' => $body,
            ],
            [
                'number' => ['required', 'integer', 'min:1', 'max:'.$requiredCount],
                'body' => ['required', 'string', 'max:5000'],
            ],
            [
                'number.required' => 'Nomor soal wajib diisi.',
                'number.integer' => 'Nomor soal harus berupa angka.',
                'number.min' => 'Nomor soal minimal 1.',
                'number.max' => "Nomor soal maksimal {$requiredCount} untuk tipe {$type->label()}.",
                'body.required' => 'Teks soal wajib diisi.',
                'body.max' => 'Teks soal maksimal 5000 karakter.',
            ]
        );

        if ($validator->fails()) {
            $this->skip($line, $validator->errors()->all());

            return;
        }

        $existing = Question::query()
            ->where('question_bank_id', $this->questionBank->id)
            ->where('type', $type->value)
            ->where('number', $number)
            ->exists();

        if ($existing) {
            $this->skip($line, ["Nomor soal {$number} pada tipe {$type->label()} sudah digunakan."]);

            return;
        }

        $options = $this->collectOptions($row, $type);
        $keys = $this->parseKeys($this->value($row, ['kunci', 'key', 'jawaban_benar']));
        $pairs = $this->parsePairs($this->value($row, ['pasangan', 'pairs', 'couple']));
        $answers = $this->parseAnswers($this->value($row, ['isian', 'jawaban', 'answer']));

        $answerError = $this->validateAnswers($type, $options, $keys, $pairs, $answers);

        if ($answerError !== null) {
            $this->skip($line, [$answerError]);

            return;
        }

        try {
            DB::transaction(function () use ($type, $number, $body, $options, $keys, $pairs, $answers): void {
                $score = (float) $this->questionBank->{$type->scoreColumn()};

                $question = Question::create([
                    'question_bank_id' => $this->questionBank->id,
                    'school_id' => $this->questionBank->school_id,
                    'subject_id' => $this->questionBank->subject_id,
                    'exam_type_id' => $this->questionBank->exam_type_id,
                    'class_id' => null,
                    'created_by' => $this->createdBy,
                    'type' => $type,
                    'number' => $number,
                    'body' => $this->normalizeBody($body),
                    'score' => $score,
                    'is_active' => true,
                ]);

                $correctCount = count($keys);
                $optionScore = $correctCount > 0 ? round($score / $correctCount, 2) : 0.0;

                foreach ($options as $index => $text) {
                    $label = chr(65 + $index);

                    QuestionOption::create([
                        'question_id' => $question->id,
                        'label' => $label,
                        'text' => $this->normalizeBody($text),
                        'is_correct' => in_array($label, $keys, true),
                        'score' => in_array($label, $keys, true) ? $optionScore : null,
                        'order' => $index + 1,
                    ]);
                }

                foreach ($pairs as $index => [$left, $right]) {
                    QuestionPair::create([
                        'question_id' => $question->id,
                        'left_text' => $left,
                        'right_text' => $right,
                        'order' => $index + 1,
                    ]);
                }

                if ($answers !== []) {
                    QuestionBlank::create([
                        'question_id' => $question->id,
                        'answers' => $answers,
                        'score' => $score,
                        'order' => 1,
                    ]);
                }
            });
        } catch (Throwable $exception) {
            report($exception);

            $this->skip($line, ['Baris tidak dapat disimpan.']);

            return;
        }

        $this->imported++;
    }

    /**
     * Validate the answers of a row against the question type.
     *
     * @param  list<string>  $options
     * @param  list<string>  $keys
     * @param  list<array{0: string, 1: string}>  $pairs
     * @param  list<string>  $answers
     */
    private function validateAnswers(
        QuestionType $type,
        array $options,
        array $keys,
        array $pairs,
        array $answers,
    ): ?string {
        if ($type->usesOptions()) {
            $expected = (int) $this->questionBank->{$type->optionCountColumn()};

            if (count($options) !== $expected) {
                return "Jumlah pilihan jawaban harus tepat {$expected} pilihan.";
            }

            foreach ($options as $option) {
                if (trim($option) === '') {
                    return "Semua {$expected} pilihan jawaban wajib diisi.";
                }
            }

            if ($keys === []) {
                return 'Kunci jawaban wajib diisi.';
            }

            foreach ($keys as $key) {
                if (! in_array($key, array_map(fn (int $index): string => chr(65 + $index), array_keys($options)), true)) {
                    return "Kunci jawaban {$key} tidak sesuai dengan pilihan jawaban.";
                }
            }

            if ($type === QuestionType::PilihanGanda && count($keys) !== 1) {
                return 'Pilihan ganda harus memiliki tepat satu kunci jawaban.';
            }

            if ($type === QuestionType::PilihanGandaKompleks && count($keys) < 2) {
                return 'Pilihan ganda kompleks harus memiliki minimal dua kunci jawaban.';
            }
        }

        if ($type->usesPairs() && count($pairs) < 2) {
            return 'Menjodohkan harus memiliki minimal dua pasangan pada kolom pasangan.';
        }

        if ($type->usesBlanks() && $answers === []) {
            return 'Minimal satu jawaban yang diterima wajib diisi.';
        }

        return null;
    }

    /**
     * Collect the answer options of a row according to the bank option count.
     *
     * @param  array<string, mixed>  $row
     * @return list<string>
     */
    private function collectOptions(array $row, QuestionType $type): array
    {
        $column = $type->optionCountColumn();

        if ($column === null) {
            return [];
        }

        $expected = (int) $this->questionBank->{$column};
        $options = [];

        for ($index = 0; $index < $expected; $index++) {
            $letter = chr(97 + $index);

            $options[] = trim((string) $this->value($row, [
                'opsi_'.$letter,
                'opsi'.$letter,
                'pilihan_'.$letter,
                'pilihan'.$letter,
            ]));
        }

        return $options;
    }

    /**
     * Normalize the question type input into the enum.
     */
    private function normalizeType(mixed $value): ?QuestionType
    {
        $type = mb_strtolower(trim((string) $value));

        return match ($type) {
            'pilihan_ganda', 'pg', 'pilihan tunggal', 'single' => QuestionType::PilihanGanda,
            'pilihan_ganda_kompleks', 'pg_kompleks', 'pg kompleks', 'pk', 'kompleks', 'complex' => QuestionType::PilihanGandaKompleks,
            'menjodohkan', 'mj', 'mencocokkan', 'matching' => QuestionType::Menjodohkan,
            'isian_singkat', 'isian', 'is' => QuestionType::IsianSingkat,
            'esai', 'es', 'essay' => QuestionType::Esai,
            default => null,
        };
    }

    /**
     * Normalize plain text into question markup.
     */
    private function normalizeBody(string $body): string
    {
        $body = trim($body);

        if ($body === '') {
            return '';
        }

        if (str_starts_with($body, '<')) {
            return $body;
        }

        return '<p>'.nl2br(e($body)).'</p>';
    }

    /**
     * Parse the answer key of a multiple choice question.
     *
     * @return list<string>
     */
    private function parseKeys(mixed $value): array
    {
        $keys = preg_split('/[\s,;]+/', mb_strtoupper(trim((string) $value))) ?: [];

        return collect($keys)
            ->map(fn (string $key): string => trim($key))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Parse the matching pairs of a menjodohkan question.
     *
     * @return list<array{0: string, 1: string}>
     */
    private function parsePairs(mixed $value): array
    {
        $pairs = [];

        foreach (explode(';', (string) $value) as $chunk) {
            if (! str_contains($chunk, '=')) {
                continue;
            }

            [$left, $right] = array_pad(explode('=', $chunk, 2), 2, '');

            $left = trim($left);
            $right = trim($right);

            if ($left !== '' && $right !== '') {
                $pairs[] = [$left, $right];
            }
        }

        return $pairs;
    }

    /**
     * Parse the accepted answers of an isian question.
     *
     * @return list<string>
     */
    private function parseAnswers(mixed $value): array
    {
        return collect(explode(';', (string) $value))
            ->map(fn (string $answer): string => trim($answer))
            ->filter()
            ->unique(fn (string $answer): string => mb_strtolower($answer))
            ->values()
            ->all();
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
