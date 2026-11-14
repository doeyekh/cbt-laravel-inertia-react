<?php

namespace Database\Seeders;

use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\QuestionBank;
use Illuminate\Database\Seeder;

class QuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $questionBank = QuestionBank::query()->orderBy('id')->first();

        if ($questionBank === null) {
            return;
        }

        $questions = [
            [
                'type' => QuestionType::PilihanGanda,
                'number' => 1,
                'body' => 'Proses tumbuhan membuat makanan dengan bantuan cahaya matahari disebut ...',
                'options' => [
                    ['label' => 'A', 'text' => 'Respirasi', 'is_correct' => false],
                    ['label' => 'B', 'text' => 'Fotosintesis', 'is_correct' => true],
                    ['label' => 'C', 'text' => 'Transpirasi', 'is_correct' => false],
                    ['label' => 'D', 'text' => 'Osmosis', 'is_correct' => false],
                ],
            ],
            [
                'type' => QuestionType::PilihanGanda,
                'number' => 2,
                'body' => 'Bagian tumbuhan yang menyerap air dan mineral dari tanah adalah ...',
                'options' => [
                    ['label' => 'A', 'text' => 'Batang', 'is_correct' => false],
                    ['label' => 'B', 'text' => 'Daun', 'is_correct' => false],
                    ['label' => 'C', 'text' => 'Akar', 'is_correct' => true],
                    ['label' => 'D', 'text' => 'Bunga', 'is_correct' => false],
                ],
            ],
            [
                'type' => QuestionType::PilihanGandaKompleks,
                'number' => 1,
                'body' => 'Pernyataan yang benar tentang respirasi sel ...',
                'options' => [
                    ['label' => 'A', 'text' => 'Menghasilkan ATP', 'is_correct' => true],
                    ['label' => 'B', 'text' => 'Terjadi di sitoplasma', 'is_correct' => true],
                    ['label' => 'C', 'text' => 'Tidak menghasilkan energi', 'is_correct' => false],
                    ['label' => 'D', 'text' => 'Sama dengan fotosintesis', 'is_correct' => false],
                ],
            ],
            [
                'type' => QuestionType::Menjodohkan,
                'number' => 1,
                'body' => 'Pasangkan organ tubuh dengan fungsinya.',
                'pairs' => [
                    ['left_text' => 'Jantung', 'right_text' => 'Memompa darah'],
                    ['left_text' => 'Paru-paru', 'right_text' => 'Pertukaran gas'],
                    ['left_text' => 'Ginjal', 'right_text' => 'Menyaring darah'],
                ],
            ],
            [
                'type' => QuestionType::IsianSingkat,
                'number' => 1,
                'body' => 'Zat hijau pada tumbuhan yang berperan menangkap energi cahaya disebut ...',
                'blanks' => [
                    ['answers' => ['klorofil', 'Klorofil']],
                ],
            ],
            [
                'type' => QuestionType::Esai,
                'number' => 1,
                'body' => 'Jelaskan perbedaan antara respirasi aerob dan respirasi anaerob beserta dampaknya bagi tumbuhan!',
            ],
        ];

        foreach ($questions as $question) {
            $questionScore = (float) $questionBank->{$question['type']->scoreColumn()};
            $correctOptions = collect($question['options'] ?? [])
                ->filter(fn (array $option): bool => (bool) $option['is_correct'])
                ->count();
            $defaultOptionScore = $correctOptions > 0
                ? round($questionScore / $correctOptions, 2)
                : 0.0;

            $record = Question::query()->firstOrCreate(
                [
                    'question_bank_id' => $questionBank->id,
                    'number' => $question['number'],
                    'type' => $question['type'],
                ],
                [
                    'school_id' => $questionBank->school_id,
                    'subject_id' => $questionBank->subject_id,
                    'exam_type_id' => $questionBank->exam_type_id,
                    'body' => $question['body'],
                    'score' => $questionScore,
                    'is_active' => true,
                ]
            );

            foreach ($question['options'] ?? [] as $index => $option) {
                $record->options()->firstOrCreate(
                    ['order' => $index + 1],
                    [
                        'label' => $option['label'],
                        'text' => $option['text'],
                        'is_correct' => $option['is_correct'],
                        'score' => $option['is_correct'] ? $defaultOptionScore : null,
                    ]
                );
            }

            foreach ($question['pairs'] ?? [] as $index => $pair) {
                $record->pairs()->firstOrCreate(
                    ['order' => $index + 1],
                    [
                        'left_text' => $pair['left_text'],
                        'right_text' => $pair['right_text'],
                    ]
                );
            }

            foreach ($question['blanks'] ?? [] as $index => $blank) {
                $record->blanks()->firstOrCreate(
                    ['order' => $index + 1],
                    [
                        'answers' => $blank['answers'],
                        'score' => $questionScore,
                    ]
                );
            }
        }
    }
}
