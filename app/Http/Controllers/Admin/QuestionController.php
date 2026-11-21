<?php

namespace App\Http\Controllers\Admin;

use App\Enums\QuestionType;
use App\Http\Controllers\Admin\Concerns\AuthorizesQuestionAccess;
use App\Http\Controllers\Admin\Concerns\PreparesQuestionSheet;
use App\Http\Controllers\Admin\Concerns\PresentsQuestionBank;
use App\Http\Controllers\Admin\Concerns\SyncsQuestionAnswers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreQuestionRequest;
use App\Http\Requests\Admin\UpdateQuestionRequest;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionBlank;
use App\Models\QuestionOption;
use App\Models\QuestionPair;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuestionController extends Controller
{
    use AuthorizesQuestionAccess;
    use PreparesQuestionSheet;
    use PresentsQuestionBank;
    use SyncsQuestionAnswers;

    /**
     * Display the question slots of the given question bank.
     */
    public function index(Request $request, QuestionBank $questionBank): Response
    {
        $this->authorizeQuestionAccess($request->user());
        $this->authorizeRecordAccess($request->user(), $questionBank);

        $typeFilter = QuestionType::tryFrom((string) $request->string('type'));

        if ($typeFilter === null) {
            $typeFilter = collect(QuestionType::cases())
                ->first(fn (QuestionType $type): bool => (int) $questionBank->{$type->countColumn()} > 0)
                ?? QuestionType::cases()[0];
        }

        $questions = $questionBank->questions()
            ->where('type', $typeFilter->value)
            ->with([
                'options' => fn ($query) => $query->orderBy('order'),
                'pairs' => fn ($query) => $query->orderBy('order'),
                'blanks' => fn ($query) => $query->orderBy('order'),
            ])
            ->orderBy('number')
            ->get();

        $slots = $this->buildSlots($questionBank, $typeFilter, $questions);

        $activeNumber = (int) $request->integer('number');

        if ($activeNumber < 1 || $activeNumber > $slots->count()) {
            $activeNumber = 1;
        }

        $activeQuestion = $questions->firstWhere('number', $activeNumber);

        $counts = [];

        foreach (QuestionType::cases() as $type) {
            $counts[$type->value] = $questionBank->questions()
                ->where('type', $type->value)
                ->count();
        }

        return Inertia::render('Questions/Index', [
            'title' => 'Soal '.$questionBank->name,
            'questionBank' => $this->presentQuestionBank($questionBank),
            'question' => $activeQuestion ? $this->presentQuestion($activeQuestion) : null,
            'slots' => $slots,
            'types' => $this->typeOptions(),
            'counts' => $counts,
            'filters' => [
                'type' => $typeFilter->value,
                'number' => $activeNumber,
            ],
        ]);
    }

    /**
     * Display all questions of the given question bank in read-only mode.
     */
    public function preview(Request $request, QuestionBank $questionBank): Response
    {
        $this->authorizeQuestionAccess($request->user());
        $this->authorizeRecordAccess($request->user(), $questionBank);

        $questions = $questionBank->questions()
            ->with([
                'options' => fn ($query) => $query->orderBy('order'),
                'pairs' => fn ($query) => $query->orderBy('order'),
                'blanks' => fn ($query) => $query->orderBy('order'),
            ])
            ->orderBy('number')
            ->get();

        $grouped = [];

        foreach (QuestionType::cases() as $type) {
            $grouped[$type->value] = $questions
                ->where('type', $type->value)
                ->map(fn (Question $question): array => $this->presentQuestion($question))
                ->values()
                ->all();
        }

        $counts = [];

        foreach (QuestionType::cases() as $type) {
            $counts[$type->value] = count($grouped[$type->value]);
        }

        return Inertia::render('Questions/Preview', [
            'title' => 'Lihat Soal '.$questionBank->name,
            'questionBank' => $this->presentQuestionBank($questionBank),
            'questions' => $grouped,
            'counts' => $counts,
            'types' => $this->typeOptions(),
        ]);
    }

    /**
     * Print the questions of the given question bank as a paper sheet.
     */
    public function print(Request $request, QuestionBank $questionBank): StreamedResponse
    {
        $this->authorizeQuestionAccess($request->user());
        $this->authorizeRecordAccess($request->user(), $questionBank);

        $sheet = $this->prepareQuestionSheet($questionBank);

        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('chroot', [public_path(), storage_path('app/public')]);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('defaultPaperSize', 'A4');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('question.print', $sheet)->render());
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $canvas = $dompdf->getCanvas();
        $color = [0.35, 0.35, 0.4];

        $canvas->page_text(
            30,
            $canvas->get_height() - 28,
            $questionBank->code,
            'dejavu',
            8,
            $color
        );

        $canvas->page_text(
            $canvas->get_width() - 150,
            $canvas->get_height() - 28,
            'Halaman {PAGE_NUM} dari {PAGE_COUNT}',
            'dejavu',
            8,
            $color
        );

        $filename = 'Soal-'.$questionBank->code.'-'.now()->format('Ymd').'.pdf';

        return response()->stream(
            fn () => print ($dompdf->output()),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
            ]
        );
    }

    /**
     * Store a newly created question in the given question bank.
     */
    public function store(StoreQuestionRequest $request, QuestionBank $questionBank): RedirectResponse
    {
        $this->authorizeQuestionManage($request->user());
        $this->authorizeRecordAccess($request->user(), $questionBank);

        $data = $request->validated();

        $question = DB::transaction(function () use ($data, $questionBank, $request): Question {
            $type = QuestionType::from((string) $data['type']);

            $question = Question::create([
                'question_bank_id' => $questionBank->id,
                'school_id' => $questionBank->school_id,
                'subject_id' => $questionBank->subject_id,
                'exam_type_id' => $questionBank->exam_type_id,
                'class_id' => null,
                'created_by' => $request->user()?->id,
                'type' => $type,
                'number' => (int) $data['number'],
                'body' => trim((string) $data['body']),
                'score' => (float) $questionBank->{$type->scoreColumn()},
                'is_active' => (bool) ($data['is_active'] ?? true),
                'image_path' => $request->file('image')?->store('questions/images', 'public'),
                'audio_path' => $request->file('audio')?->store('questions/audio', 'public'),
            ]);

            $this->syncAnswers($question, $data, $request);

            return $question;
        });

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Soal Disimpan',
            'description' => "Soal nomor {$question->number} ({$question->type->label()}) telah disimpan.",
        ]);
    }

    /**
     * Update the specified question.
     */
    public function update(
        UpdateQuestionRequest $request,
        QuestionBank $questionBank,
        Question $question,
    ): RedirectResponse {
        $this->authorizeQuestionManage($request->user());
        $this->authorizeRecordAccess($request->user(), $questionBank);
        $this->ensureQuestionBelongsToBank($question, $questionBank);

        $data = $request->validated();

        DB::transaction(function () use ($question, $data, $questionBank, $request): void {
            $type = QuestionType::from((string) $data['type']);

            $question->update([
                'type' => $type,
                'number' => (int) $data['number'],
                'body' => trim((string) $data['body']),
                'score' => (float) $questionBank->{$type->scoreColumn()},
                'is_active' => (bool) ($data['is_active'] ?? $question->is_active),
            ]);

            if ($request->hasFile('image')) {
                $this->deleteMedia($question->image_path);
                $question->image_path = $request->file('image')->store('questions/images', 'public');
                $question->save();
            } elseif ($request->boolean('remove_image')) {
                $this->deleteMedia($question->image_path);
                $question->image_path = null;
                $question->save();
            }

            if ($request->hasFile('audio')) {
                $this->deleteMedia($question->audio_path);
                $question->audio_path = $request->file('audio')->store('questions/audio', 'public');
                $question->save();
            } elseif ($request->boolean('remove_audio')) {
                $this->deleteMedia($question->audio_path);
                $question->audio_path = null;
                $question->save();
            }

            $this->syncAnswers($question, $data, $request);
        });

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Soal Diperbarui',
            'description' => "Soal nomor {$question->number} ({$question->type->label()}) telah diperbarui.",
        ]);
    }

    /**
     * Remove the specified question.
     */
    public function destroy(
        Request $request,
        QuestionBank $questionBank,
        Question $question,
    ): RedirectResponse {
        $this->authorizeQuestionManage($request->user());
        $this->authorizeRecordAccess($request->user(), $questionBank);
        $this->ensureQuestionBelongsToBank($question, $questionBank);

        $this->deleteMedia($question->image_path);
        $this->deleteMedia($question->audio_path);

        $question->options->each(function (QuestionOption $option): void {
            $this->deleteMedia($option->image_path);
            $this->deleteMedia($option->audio_path);
        });

        $number = $question->number;
        $type = $question->type->label();

        $question->delete();

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Soal Dihapus',
            'description' => "Soal nomor {$number} ({$type}) telah dihapus.",
        ]);
    }

    /**
     * Remove every question of the given question bank.
     */
    public function destroyAll(Request $request, QuestionBank $questionBank): RedirectResponse
    {
        $this->authorizeQuestionManage($request->user());
        $this->authorizeRecordAccess($request->user(), $questionBank);

        $questions = $questionBank->questions()->with('options')->get();

        if ($questions->isEmpty()) {
            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Tidak Ada Soal',
                'description' => 'Bank soal ini belum memiliki soal untuk dikosongkan.',
            ]);
        }

        $deleted = 0;

        DB::transaction(function () use ($questions, &$deleted): void {
            foreach ($questions as $question) {
                $this->deleteMedia($question->image_path);
                $this->deleteMedia($question->audio_path);

                $question->options->each(function (QuestionOption $option): void {
                    $this->deleteMedia($option->image_path);
                    $this->deleteMedia($option->audio_path);
                });

                $question->delete();
                $deleted++;
            }
        });

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Soal Dikosongkan',
            'description' => "{$deleted} soal beserta media gambarnya telah dihapus dari bank soal ini.",
        ]);
    }

    /**
     * Build the question slots of the given type.
     *
     * @param  Collection<int, Question>  $questions
     * @return Collection<int, array{number: int, question_id: int|null, filled: bool}>
     */
    private function buildSlots(QuestionBank $questionBank, QuestionType $type, $questions): Collection
    {
        $requiredCount = (int) $questionBank->{$type->countColumn()};
        $highestNumber = (int) ($questions->max('number') ?? 0);
        $totalSlots = max($requiredCount, $highestNumber);

        $slots = collect();

        for ($number = 1; $number <= $totalSlots; $number++) {
            $question = $questions->firstWhere('number', $number);

            $slots->push([
                'number' => $number,
                'question_id' => $question?->id,
                'filled' => $question !== null,
            ]);
        }

        return $slots;
    }

    /**
     * Ensure the given question belongs to the question bank in the route.
     */
    private function ensureQuestionBelongsToBank(Question $question, QuestionBank $questionBank): void
    {
        if ((int) $question->question_bank_id !== (int) $questionBank->id) {
            abort(404);
        }
    }

    /**
     * Transform a question into its frontend representation.
     *
     * @return array<string, mixed>
     */
    private function presentQuestion(Question $question): array
    {
        return [
            'id' => $question->id,
            'question_bank_id' => $question->question_bank_id,
            'type' => $question->type->value,
            'type_label' => $question->type->label(),
            'number' => $question->number,
            'body' => $question->body,
            'score' => $question->score,
            'is_active' => $question->is_active,
            'image_path' => $question->image_path,
            'audio_path' => $question->audio_path,
            'image_url' => $question->image_path ? Storage::disk('public')->url($question->image_path) : null,
            'audio_url' => $question->audio_path ? Storage::disk('public')->url($question->audio_path) : null,
            'options' => $question->options->map(fn (QuestionOption $option): array => [
                'id' => $option->id,
                'label' => $option->label,
                'text' => $option->text,
                'is_correct' => $option->is_correct,
                'score' => $option->score,
                'order' => $option->order,
                'image_path' => $option->image_path,
                'audio_path' => $option->audio_path,
                'image_url' => $option->image_path ? Storage::disk('public')->url($option->image_path) : null,
                'audio_url' => $option->audio_path ? Storage::disk('public')->url($option->audio_path) : null,
            ])->values()->all(),
            'pairs' => $question->pairs->map(fn (QuestionPair $pair): array => [
                'id' => $pair->id,
                'left_text' => $pair->left_text,
                'right_text' => $pair->right_text,
                'order' => $pair->order,
            ])->values()->all(),
            'blanks' => $question->blanks->map(fn (QuestionBlank $blank): array => [
                'id' => $blank->id,
                'answers' => $blank->answers,
                'score' => $blank->score,
                'order' => $blank->order,
            ])->values()->all(),
        ];
    }

    /**
     * Get the question type options for the form.
     *
     * @return array<int, array{value: string, label: string}>
     */
    private function typeOptions(): array
    {
        return array_map(
            fn (QuestionType $type): array => [
                'value' => $type->value,
                'label' => $type->label(),
            ],
            QuestionType::cases(),
        );
    }
}
