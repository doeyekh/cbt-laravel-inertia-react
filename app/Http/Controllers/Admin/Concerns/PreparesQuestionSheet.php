<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\QuestionBank;

trait PreparesQuestionSheet
{
    /**
     * Build the data used to render the printable question sheet.
     *
     * Soal disusun menjadi satu daftar berurutan dengan penomoran berurutan
     * (1, 2, 3, ...) tanpa pengelompokan per tipe.
     *
     * @return array<string, mixed>
     */
    protected function prepareQuestionSheet(QuestionBank $questionBank): array
    {
        $questions = $questionBank->questions()
            ->with([
                'options' => fn ($query) => $query->orderBy('order'),
                'pairs' => fn ($query) => $query->orderBy('order'),
                'blanks' => fn ($query) => $query->orderBy('order'),
            ])
            ->orderBy('number')
            ->get();

        $items = [];
        $number = 0;
        $totalScore = 0.0;

        foreach (QuestionType::cases() as $type) {
            $typeQuestions = $questions->where('type', $type->value);

            if ($typeQuestions->isEmpty()) {
                continue;
            }

            $score = (float) $questionBank->{$type->scoreColumn()};

            $totalScore += $score * $typeQuestions->count();

            foreach ($typeQuestions as $question) {
                $number++;

                $items[] = array_merge(
                    $this->prepareSheetQuestion($question),
                    [
                        'number' => $number,
                        'type_label' => $type->label(),
                        'type_short' => $type->shortLabel(),
                        'score' => $score,
                    ]
                );
            }
        }

        return [
            'bank' => [
                'name' => $questionBank->name,
                'code' => $questionBank->code,
                'school_name' => $questionBank->school?->name,
                'subject_name' => $questionBank->subject?->name,
                'exam_type_name' => $questionBank->examType?->name,
                'classes' => $questionBank->classes->pluck('name')->values()->all(),
                'print_date' => now()->translatedFormat('d F Y'),
            ],
            'questions' => $items,
            'totalQuestions' => count($items),
            'totalScore' => $totalScore,
        ];
    }

    /**
     * Build the printable representation of a single question.
     *
     * @return array<string, mixed>
     */
    protected function prepareSheetQuestion(Question $question): array
    {
        return [
            'number' => $question->number,
            'body' => $this->prepareQuestionHtml($question->body),
            'image' => $this->prepareSheetImage($this->localMediaPath($question->image_path)),
            'audio' => $question->audio_path,
            'options' => $question->options
                ->map(fn ($option): array => [
                    'label' => $option->label,
                    'text' => $this->prepareQuestionHtml($option->text),
                    'image' => $this->prepareSheetImage($this->localMediaPath($option->image_path), 55, 35),
                ])
                ->values()
                ->all(),
            'pairs' => $question->pairs
                ->map(fn ($pair): array => [
                    'left_text' => $pair->left_text,
                    'right_text' => $pair->right_text,
                ])
                ->values()
                ->all(),
            'blank_count' => $question->blanks->count(),
            'is_complex' => $question->type === QuestionType::PilihanGandaKompleks,
            'is_essay' => $question->type === QuestionType::Esai,
        ];
    }

    /**
     * Convert the stored question markup into markup that can be printed.
     *
     * Script dan atribut event dibuang, formula LaTeX ditampilkan sebagai kode
     * LaTeX, dan gambar lokal memakai path absolut serta ukuran eksplisit
     * karena DomPDF tidak mendukung `max-width` pada gambar.
     */
    protected function prepareQuestionHtml(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        $html = preg_replace('#<(script|style|iframe|object|embed)[^>]*>.*?</\1>#si', '', $html) ?? $html;
        $html = preg_replace('#<(script|style|iframe|object|embed)[^>]*>#si', '', $html) ?? $html;
        $html = preg_replace('#\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $html) ?? $html;

        $html = preg_replace_callback(
            '#<span[^>]*class="math-node"[^>]*data-latex="([^"]*)"[^>]*>.*?</span>#si',
            fn (array $matches): string => '<span class="math">'
                .htmlspecialchars(html_entity_decode($matches[1], ENT_QUOTES), ENT_QUOTES)
                .'</span>',
            $html
        ) ?? $html;

        return preg_replace_callback(
            '#<img([^>]*?)src="([^"]+)"([^>]*)>#i',
            function (array $matches): string {
                $image = $this->prepareSheetImage($this->localMediaUrl($matches[2]));

                if ($image === null) {
                    return $matches[0];
                }

                return '<img'.$matches[1].'src="'.$image['path'].'"'
                    .' style="width: '.$image['width'].'; height: '.$image['height'].';"'
                    .$matches[3].'>';
            },
            $html
        ) ?? $html;
    }

    /**
     * Resolve the absolute path of a stored media file.
     */
    protected function localMediaPath(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        return storage_path('app/public/'.$path);
    }

    /**
     * Resolve a media URL that points to the local public storage.
     */
    protected function localMediaUrl(string $url): string
    {
        $path = str_replace('\\', '/', $url);
        $path = preg_replace('#^https?://[^/]+#i', '', $path) ?? $path;

        if (! str_starts_with($path, '/storage/')) {
            return $url;
        }

        return storage_path('app/public/'.substr($path, strlen('/storage/')));
    }

    /**
     * Build the printable image descriptor with an explicit display size.
     *
     * DomPDF renders images at their natural size and ignores `max-width`, so
     * the size is scaled down to fit the printable area beforehand.
     *
     * @return array{path: string, width: string, height: string}|null
     */
    protected function prepareSheetImage(?string $absolutePath, float $maxWidth = 85, float $maxHeight = 50): ?array
    {
        if ($absolutePath === null || ! is_file($absolutePath)) {
            return null;
        }

        $size = @getimagesize($absolutePath);

        if ($size === false || $size[0] === 0 || $size[1] === 0) {
            return ['path' => $absolutePath, 'width' => 'auto', 'height' => 'auto'];
        }

        $naturalWidth = $size[0] * 25.4 / 96;
        $naturalHeight = $size[1] * 25.4 / 96;

        $scale = min($maxWidth / $naturalWidth, $maxHeight / $naturalHeight, 1);

        return [
            'path' => $absolutePath,
            'width' => round($naturalWidth * $scale, 1).'mm',
            'height' => round($naturalHeight * $scale, 1).'mm',
        ];
    }
}
