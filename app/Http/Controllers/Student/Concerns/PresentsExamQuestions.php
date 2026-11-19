<?php

namespace App\Http\Controllers\Student\Concerns;

use App\Models\Question;
use App\Models\QuestionBlank;
use App\Models\QuestionOption;
use App\Models\QuestionPair;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

trait PresentsExamQuestions
{
    /**
     * Build the student-facing representation of the exam questions.
     *
     * Payload ini dibaca langsung oleh browser peserta, sehingga kunci
     * jawaban (option `is_correct` dan `blank` `answers`) tidak boleh ikut
     * dikirim. Field yang dikirim hanya yang dibutuhkan halaman pengerjaan.
     *
     * @param  Collection<int, Question>  $questions
     * @return array<int, array<string, mixed>>
     */
    protected function presentExamQuestions(Collection $questions): array
    {
        return $questions
            ->map(fn (Question $question): array => [
                'id' => $question->id,
                'number' => $question->number,
                'type' => $question->type->value,
                'body' => $question->body,
                'score' => $question->score,
                'image_path' => $question->image_path,
                'audio_path' => $question->audio_path,
                'image_url' => $question->image_path
                    ? Storage::disk('public')->url($question->image_path)
                    : null,
                'options' => $question->options
                    ->map(fn (QuestionOption $option): array => [
                        'id' => $option->id,
                        'label' => $option->label,
                        'text' => $option->text,
                        'order' => $option->order,
                        'image_path' => $option->image_path,
                        'audio_path' => $option->audio_path,
                        'image_url' => $option->image_path
                            ? Storage::disk('public')->url($option->image_path)
                            : null,
                    ])
                    ->values()
                    ->all(),
                'pairs' => $question->pairs
                    ->map(fn (QuestionPair $pair): array => [
                        'id' => $pair->id,
                        'left_text' => $pair->left_text,
                        'right_text' => $pair->right_text,
                        'order' => $pair->order,
                    ])
                    ->values()
                    ->all(),
                'blanks' => $question->blanks
                    ->map(fn (QuestionBlank $blank): array => [
                        'id' => $blank->id,
                        'order' => $blank->order,
                    ])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }
}
