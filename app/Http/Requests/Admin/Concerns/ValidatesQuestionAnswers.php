<?php

namespace App\Http\Requests\Admin\Concerns;

use App\Enums\QuestionType;
use App\Models\QuestionBank;
use Illuminate\Support\Collection;
use Illuminate\Validation\Validator;

trait ValidatesQuestionAnswers
{
    /**
     * Get the validation rules for the answer payloads.
     *
     * @return array<string, mixed>
     */
    protected function questionAnswerRules(): array
    {
        return [
            'options' => ['sometimes', 'array'],
            'options.*.id' => ['sometimes', 'nullable', 'integer'],
            'options.*.label' => ['required_with:options', 'string', 'max:10'],
            'options.*.text' => ['required_with:options', 'string', 'max:1000'],
            'options.*.is_correct' => ['sometimes', 'boolean'],
            'options.*.score' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:999.99'],
            'options.*.image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'options.*.audio' => ['nullable', 'file', 'mimes:mp3,wav,mpeg,ogg,m4a', 'max:10240'],
            'options.*.remove_image' => ['sometimes', 'boolean'],
            'options.*.remove_audio' => ['sometimes', 'boolean'],
            'pairs' => ['sometimes', 'array'],
            'pairs.*.left_text' => ['required_with:pairs', 'string', 'max:500'],
            'pairs.*.right_text' => ['required_with:pairs', 'string', 'max:500'],
            'blanks' => ['sometimes', 'array'],
            'blanks.*.answers' => ['required_with:blanks', 'array', 'min:1'],
            'blanks.*.answers.*' => ['required', 'string', 'max:255'],
            'blanks.*.score' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:999.99'],
        ];
    }

    /**
     * Validate the answer payload against the question type.
     */
    protected function validateQuestionAnswers(Validator $validator, QuestionType $type, QuestionBank $questionBank): void
    {
        if ($type->usesOptions()) {
            $this->validateQuestionOptions($validator, $type, $questionBank);
        }

        if ($type->usesPairs()) {
            $this->validateQuestionPairs($validator);
        }

        if ($type->usesBlanks()) {
            $this->validateQuestionBlanks($validator, $type);
        }
    }

    /**
     * Validate the answer options of a multiple choice question.
     */
    private function validateQuestionOptions(Validator $validator, QuestionType $type, QuestionBank $questionBank): void
    {
        $options = $this->answerItems('options');
        $requiredOptionCount = (int) $questionBank->{$type->optionCountColumn()};

        if ($options->count() !== $requiredOptionCount) {
            $validator->errors()->add('options', "Jumlah pilihan jawaban harus tepat {$requiredOptionCount} pilihan.");

            return;
        }

        $labels = $options->pluck('label')->map(fn ($label): string => strtoupper(trim((string) $label)));

        if ($labels->duplicates()->isNotEmpty()) {
            $validator->errors()->add('options', 'Label opsi harus unik.');
        }

        $correctOptions = $options->filter(
            fn (array $option): bool => filter_var($option['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN)
        );

        if ($type === QuestionType::PilihanGanda && $correctOptions->count() !== 1) {
            $validator->errors()->add('options', 'Pilihan ganda harus memiliki tepat satu jawaban benar.');
        }

        if ($type === QuestionType::PilihanGandaKompleks) {
            if ($correctOptions->count() < 2) {
                $validator->errors()->add('options', 'Pilihan ganda kompleks harus memiliki minimal dua jawaban benar.');

                return;
            }

            $hasInvalidScore = $correctOptions->contains(
                fn (array $option): bool => isset($option['score'])
                    && $option['score'] !== ''
                    && (float) $option['score'] <= 0
            );

            if ($hasInvalidScore) {
                $validator->errors()->add('options', 'Bobot jawaban benar pada pilihan ganda kompleks harus lebih dari 0.');
            }
        }
    }

    /**
     * Validate the matching pairs of a matching question.
     */
    private function validateQuestionPairs(Validator $validator): void
    {
        $pairs = $this->answerItems('pairs');

        if ($pairs->count() < 2 || $pairs->count() > 10) {
            $validator->errors()->add('pairs', 'Jumlah pasangan harus antara 2 sampai 10.');

            return;
        }

        $left = $pairs->pluck('left_text')->map(fn ($text): string => trim((string) $text));
        $right = $pairs->pluck('right_text')->map(fn ($text): string => trim((string) $text));

        if ($left->contains('')) {
            $validator->errors()->add('pairs', 'Kolom kiri pada setiap pasangan wajib diisi.');
        }

        if ($right->contains('')) {
            $validator->errors()->add('pairs', 'Kolom kanan pada setiap pasangan wajib diisi.');
        }

        if ($left->duplicates()->isNotEmpty()) {
            $validator->errors()->add('pairs', 'Nilai pada kolom kiri harus unik.');
        }

        if ($right->duplicates()->isNotEmpty()) {
            $validator->errors()->add('pairs', 'Nilai pada kolom kanan harus unik.');
        }
    }

    /**
     * Validate the answer blanks of a fill in question.
     */
    private function validateQuestionBlanks(Validator $validator, QuestionType $type): void
    {
        $blanks = $this->answerItems('blanks');

        if ($type === QuestionType::IsianSingkat && $blanks->count() !== 1) {
            $validator->errors()->add('blanks', 'Isian singkat harus memiliki tepat satu isian.');

            return;
        }

        $blanks->each(function (array $blank, int $index) use ($validator): void {
            $answers = collect($blank['answers'] ?? [])
                ->map(fn ($answer): string => trim((string) $answer))
                ->filter()
                ->unique(fn (string $answer): string => mb_strtolower($answer))
                ->values();

            if ($answers->isEmpty()) {
                $validator->errors()->add("blanks.{$index}.answers", 'Minimal satu jawaban yang diterima wajib diisi.');
            }
        });
    }

    /**
     * Get the submitted answer items of a given key as a collection.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function answerItems(string $key): Collection
    {
        return collect($this->input($key, []))
            ->filter(fn ($item): bool => is_array($item))
            ->values();
    }
}
