<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\Question;
use App\Models\QuestionBlank;
use App\Models\QuestionOption;
use App\Models\QuestionPair;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

trait SyncsQuestionAnswers
{
    /**
     * Replace the answers of a question based on its type.
     *
     * @param  array<string, mixed>  $data
     */
    protected function syncAnswers(Question $question, array $data, Request $request): void
    {
        $type = $question->type;

        if ($type->usesOptions()) {
            $this->syncOptions($question, $data, $request);
        } else {
            $this->deleteOptions($question);
        }

        $question->pairs()->delete();

        if ($type->usesPairs()) {
            $this->syncPairs($question, $data);
        }

        $question->blanks()->delete();

        if ($type->usesBlanks()) {
            $this->syncBlanks($question, $data);
        }
    }

    /**
     * Store the answer options of a multiple choice question.
     *
     * Media of an option is kept when the option is submitted again without a new
     * upload, so editing a question never drops the files that were stored before.
     *
     * @param  array<string, mixed>  $data
     */
    private function syncOptions(Question $question, array $data, Request $request): void
    {
        $options = collect($data['options'] ?? [])->values();
        $existingOptions = $question->options()->get()->keyBy('id');

        $correctOptions = $options->filter(
            fn (array $option): bool => filter_var($option['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN)
        );
        $defaultScore = $correctOptions->count() > 0
            ? round((float) $question->score / $correctOptions->count(), 2)
            : 0.0;

        $attributes = [];
        $retainedIds = [];

        foreach ($options as $index => $option) {
            $isCorrect = filter_var($option['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $score = $option['score'] ?? null;
            $existing = $this->findExistingOption($existingOptions, $option['id'] ?? null);

            if ($existing !== null) {
                $retainedIds[] = $existing->id;
            }

            $attributes[] = [
                'question_id' => $question->id,
                'label' => strtoupper(trim((string) ($option['label'] ?? chr(65 + $index)))),
                'text' => trim((string) $option['text']),
                'is_correct' => $isCorrect,
                'score' => $isCorrect
                    ? (isset($score) && $score !== '' ? (float) $score : $defaultScore)
                    : null,
                'order' => $index + 1,
                'image_path' => $this->resolveMediaPath(
                    $request->file("options.{$index}.image"),
                    $existing?->image_path,
                    $this->wantsRemoval($option, 'remove_image'),
                    'questions/options/images',
                ),
                'audio_path' => $this->resolveMediaPath(
                    $request->file("options.{$index}.audio"),
                    $existing?->audio_path,
                    $this->wantsRemoval($option, 'remove_audio'),
                    'questions/options/audio',
                ),
            ];
        }

        $question->options()->delete();

        foreach ($attributes as $attribute) {
            QuestionOption::create($attribute);
        }

        $existingOptions->each(function (QuestionOption $option) use ($retainedIds): void {
            if (in_array($option->id, $retainedIds, true)) {
                return;
            }

            $this->deleteMedia($option->image_path);
            $this->deleteMedia($option->audio_path);
        });
    }

    /**
     * Store the matching pairs of a matching question.
     *
     * @param  array<string, mixed>  $data
     */
    private function syncPairs(Question $question, array $data): void
    {
        foreach (collect($data['pairs'] ?? [])->values() as $index => $pair) {
            QuestionPair::create([
                'question_id' => $question->id,
                'left_text' => trim((string) $pair['left_text']),
                'right_text' => trim((string) $pair['right_text']),
                'order' => $index + 1,
            ]);
        }
    }

    /**
     * Store the answer blanks of a fill in question.
     *
     * @param  array<string, mixed>  $data
     */
    private function syncBlanks(Question $question, array $data): void
    {
        $blanks = collect($data['blanks'] ?? [])->values();
        $defaultScore = $blanks->count() > 0
            ? round((float) $question->score / $blanks->count(), 2)
            : 0.0;

        foreach ($blanks as $index => $blank) {
            $answers = collect($blank['answers'] ?? [])
                ->map(fn ($answer): string => trim((string) $answer))
                ->filter()
                ->unique(fn (string $answer): string => mb_strtolower($answer))
                ->values()
                ->all();

            $score = $blank['score'] ?? null;

            QuestionBlank::create([
                'question_id' => $question->id,
                'answers' => $answers,
                'score' => isset($score) && $score !== '' ? (float) $score : $defaultScore,
                'order' => $index + 1,
            ]);
        }
    }

    /**
     * Delete the answer options of a question together with their media.
     */
    private function deleteOptions(Question $question): void
    {
        $question->options()->get()->each(function (QuestionOption $option): void {
            $this->deleteMedia($option->image_path);
            $this->deleteMedia($option->audio_path);
        });

        $question->options()->delete();
    }

    /**
     * Find the stored option that matches the submitted option identifier.
     *
     * @param  Collection<int, QuestionOption>  $existingOptions
     */
    private function findExistingOption(Collection $existingOptions, mixed $id): ?QuestionOption
    {
        if ($id === null || $id === '') {
            return null;
        }

        return $existingOptions->get((int) $id);
    }

    /**
     * Determine whether the submitted option asks for a stored media to be removed.
     *
     * @param  array<string, mixed>  $option
     */
    private function wantsRemoval(array $option, string $key): bool
    {
        return filter_var($option[$key] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Resolve the stored path of a media slot.
     */
    private function resolveMediaPath(
        ?UploadedFile $upload,
        ?string $existingPath,
        bool $shouldRemove,
        string $directory,
    ): ?string {
        if ($upload !== null) {
            if ($existingPath !== null) {
                $this->deleteMedia($existingPath);
            }

            return $upload->store($directory, 'public');
        }

        if ($shouldRemove) {
            if ($existingPath !== null) {
                $this->deleteMedia($existingPath);
            }

            return null;
        }

        return $existingPath;
    }

    /**
     * Delete a stored media file.
     */
    protected function deleteMedia(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        Storage::disk('public')->delete($path);
    }
}
