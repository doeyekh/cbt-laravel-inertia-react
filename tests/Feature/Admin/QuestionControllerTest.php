<?php

namespace Tests\Feature\Admin;

use App\Enums\QuestionType;
use App\Http\Controllers\Admin\Concerns\PreparesQuestionSheet;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionBlank;
use App\Models\QuestionOption;
use App\Models\QuestionPair;
use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class QuestionControllerTest extends TestCase
{
    use PreparesQuestionSheet;
    use RefreshDatabase;

    private School $school;

    private Subject $subject;

    private QuestionBank $questionBank;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->subject = Subject::factory()->create();
        $this->actingAs(User::factory()->administrator()->create());

        $this->questionBank = QuestionBank::factory()->create([
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'pilihan_ganda_count' => 3,
            'pilihan_ganda_kompleks_count' => 2,
            'menjodohkan_count' => 2,
            'isian_count' => 2,
            'esai_count' => 1,
        ]);
    }

    public function test_index_returns_question_slots_and_the_active_question(): void
    {
        $question = $this->storeQuestion();

        $this->get(route('admin.question-banks.questions.index', $this->questionBank))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Questions/Index')
                ->where('questionBank.id', $this->questionBank->id)
                ->where('question.id', $question->id)
                ->where('question.number', 1)
                ->has('slots', 3)
                ->where('slots.0.number', 1)
                ->where('slots.0.filled', true)
                ->where('slots.0.question_id', $question->id)
                ->where('slots.1.filled', false)
                ->where('filters.type', QuestionType::PilihanGanda->value)
                ->where('filters.number', 1)
                ->where('counts.pilihan_ganda', 1)
                ->has('types', 5)
            );
    }

    public function test_print_returns_a_pdf_sheet(): void
    {
        $this->storeQuestion();

        $response = $this->get(route('admin.question-banks.questions.print', $this->questionBank));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');

        $this->assertStringStartsWith('%PDF', $response->streamedContent());
    }

    public function test_print_forbids_regular_guru(): void
    {
        $this->actingAs(User::factory()->guru()->create())
            ->get(route('admin.question-banks.questions.print', $this->questionBank))
            ->assertForbidden();
    }

    public function test_print_sheet_numbers_the_questions_continuously_without_grouping(): void
    {
        $this->storeQuestion();

        $this->storeQuestion([
            'type' => QuestionType::Menjodohkan->value,
            'number' => 1,
            'options' => [],
            'pairs' => [
                ['left_text' => 'Jantung', 'right_text' => 'Memompa darah'],
                ['left_text' => 'Ginjal', 'right_text' => 'Menyaring darah'],
            ],
        ]);

        $sheet = $this->prepareQuestionSheet($this->questionBank);

        $this->assertSame([1, 2], array_column($sheet['questions'], 'number'));
        $this->assertSame(
            ['PG', 'MJ'],
            array_column($sheet['questions'], 'type_short')
        );

        $html = view('question.print', $sheet)->render();

        $this->assertStringContainsString('1.', $html);
        $this->assertStringContainsString('2.', $html);
        $this->assertStringNotContainsString('A. PILIHAN GANDA', $html);
    }

    public function test_print_sheet_hides_the_answer_key(): void
    {
        $this->storeQuestion();

        $html = view('question.print', $this->prepareQuestionSheet($this->questionBank))->render();

        $this->assertStringContainsString('Contoh soal pilihan ganda.', $html);
        $this->assertStringContainsString('Jawaban A', $html);
        $this->assertStringNotContainsString('Jawaban Benar', $html);
    }

    public function test_print_sheet_renders_the_formula_as_plain_text_and_drops_scripts(): void
    {
        $this->storeQuestion([
            'body' => '<p>Nilai <span class="math-node" data-latex="x^2 + y^2 = z^2">\\(x^2 + y^2 = z^2\\)</span></p>'
                .'<script>alert(1)</script>',
        ]);

        $html = view('question.print', $this->prepareQuestionSheet($this->questionBank))->render();

        $this->assertStringContainsString('<span class="math">x^2 + y^2 = z^2</span>', $html);
        $this->assertStringNotContainsString('math-node', $html);
        $this->assertStringNotContainsString('<script', $html);
    }

    public function test_preview_returns_all_questions_grouped_by_type(): void
    {
        $question = $this->storeQuestion();

        $this->storeQuestion([
            'type' => QuestionType::Menjodohkan->value,
            'number' => 1,
            'options' => [],
            'pairs' => [
                ['left_text' => 'Jantung', 'right_text' => 'Memompa darah'],
                ['left_text' => 'Ginjal', 'right_text' => 'Menyaring darah'],
            ],
        ]);

        $this->get(route('admin.question-banks.questions.preview', $this->questionBank))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Questions/Preview')
                ->where('questionBank.id', $this->questionBank->id)
                ->has('questions.pilihan_ganda', 1)
                ->where('questions.pilihan_ganda.0.id', $question->id)
                ->where('questions.pilihan_ganda.0.body', 'Contoh soal pilihan ganda.')
                ->has('questions.pilihan_ganda.0.options', 4)
                ->where('questions.pilihan_ganda.0.options.0.is_correct', true)
                ->where('questions.pilihan_ganda.0.options.1.is_correct', false)
                ->has('questions.menjodohkan', 1)
                ->has('questions.menjodohkan.0.pairs', 2)
                ->has('questions.esai', 0)
                ->where('counts.pilihan_ganda', 1)
                ->where('counts.menjodohkan', 1)
                ->where('counts.esai', 0)
                ->has('types', 5)
            );
    }

    public function test_preview_returns_an_empty_list_for_a_bank_without_questions(): void
    {
        $this->get(route('admin.question-banks.questions.preview', $this->questionBank))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Questions/Preview')
                ->has('questions.pilihan_ganda', 0)
                ->where('counts.pilihan_ganda', 0)
            );
    }

    public function test_preview_forbids_regular_guru(): void
    {
        $this->actingAs(User::factory()->guru()->create())
            ->get(route('admin.question-banks.questions.preview', $this->questionBank))
            ->assertForbidden();
    }

    public function test_preview_forbids_school_admin_from_another_school(): void
    {
        $schoolAdmin = User::factory()->guru()->create();
        $this->school->update(['admin_id' => $schoolAdmin->id]);
        $otherBank = QuestionBank::factory()->create([
            'school_id' => School::factory()->create()->id,
        ]);

        $this->actingAs($schoolAdmin)
            ->get(route('admin.question-banks.questions.preview', $otherBank))
            ->assertForbidden();
    }

    public function test_index_opens_the_requested_question_number(): void
    {
        $this->storeQuestion(['number' => 1]);
        $second = $this->storeQuestion(['number' => 2]);

        $this->get(route('admin.question-banks.questions.index', [
            'questionBank' => $this->questionBank->id,
            'number' => 2,
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('question.id', $second->id)
                ->where('question.number', 2)
                ->where('filters.number', 2)
            );
    }

    public function test_index_defaults_to_the_first_type_in_the_bank_composition(): void
    {
        $this->storeQuestion([
            'type' => QuestionType::Menjodohkan->value,
            'options' => [],
            'pairs' => [
                ['left_text' => 'Jantung', 'right_text' => 'Memompa darah'],
                ['left_text' => 'Ginjal', 'right_text' => 'Menyaring darah'],
            ],
        ]);

        $this->get(route('admin.question-banks.questions.index', $this->questionBank))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.type', QuestionType::PilihanGanda->value)
                ->where('question', null)
                ->where('counts.menjodohkan', 1)
            );
    }

    public function test_index_forbids_regular_guru(): void
    {
        $this->actingAs(User::factory()->guru()->create())
            ->get(route('admin.question-banks.questions.index', $this->questionBank))
            ->assertForbidden();
    }

    public function test_index_forbids_school_admin_from_another_school(): void
    {
        $schoolAdmin = User::factory()->guru()->create();
        $this->school->update(['admin_id' => $schoolAdmin->id]);
        $otherBank = QuestionBank::factory()->create([
            'school_id' => School::factory()->create()->id,
        ]);

        $this->actingAs($schoolAdmin)
            ->get(route('admin.question-banks.questions.index', $otherBank))
            ->assertForbidden();
    }

    public function test_store_creates_a_multiple_choice_question(): void
    {
        $this->post(
            route('admin.question-banks.questions.store', $this->questionBank),
            $this->payload()
        )->assertRedirect()->assertSessionHas('toast.type', 'success');

        $question = Question::query()->firstOrFail();

        $this->assertSame($this->questionBank->id, $question->question_bank_id);
        $this->assertSame($this->school->id, $question->school_id);
        $this->assertSame($this->subject->id, $question->subject_id);
        $this->assertSame(QuestionType::PilihanGanda, $question->type);
        $this->assertSame(1, $question->number);
        $this->assertSame(50.0, $question->score);
        $this->assertSame(4, QuestionOption::query()->count());
        $this->assertSame(1, QuestionOption::query()->where('is_correct', true)->count());
    }

    public function test_store_creates_a_matching_question(): void
    {
        $this->post(
            route('admin.question-banks.questions.store', $this->questionBank),
            $this->payload([
                'type' => QuestionType::Menjodohkan->value,
                'options' => [],
                'pairs' => [
                    ['left_text' => 'Jantung', 'right_text' => 'Memompa darah'],
                    ['left_text' => 'Ginjal', 'right_text' => 'Menyaring darah'],
                ],
            ])
        )->assertRedirect();

        $this->assertSame(2, QuestionPair::query()->count());
        $this->assertSame(0, QuestionOption::query()->count());
    }

    public function test_store_creates_a_short_answer_question(): void
    {
        $this->post(
            route('admin.question-banks.questions.store', $this->questionBank),
            $this->payload([
                'type' => QuestionType::IsianSingkat->value,
                'options' => [],
                'blanks' => [
                    ['answers' => ['klorofil'], 'score' => ''],
                ],
            ])
        )->assertRedirect();

        $blank = QuestionBlank::query()->firstOrFail();

        $this->assertSame(['klorofil'], $blank->answers);
        $this->assertSame(20.0, (float) $blank->score);
    }

    public function test_update_replaces_the_pairs_of_a_matching_question(): void
    {
        Storage::fake('public');

        $question = $this->storeQuestion([
            'type' => QuestionType::Menjodohkan->value,
            'options' => [],
            'pairs' => [
                ['left_text' => 'Jantung', 'right_text' => 'Memompa darah'],
                ['left_text' => 'Ginjal', 'right_text' => 'Menyaring darah'],
            ],
        ]);

        $this->put(
            route('admin.question-banks.questions.update', [
                'questionBank' => $this->questionBank->id,
                'question' => $question->id,
            ]),
            $this->payload([
                'type' => QuestionType::Menjodohkan->value,
                'options' => [],
                'body' => 'Soal menjodohkan yang diperbarui.',
                'pairs' => [
                    ['left_text' => 'Paru-paru', 'right_text' => 'Pertukaran gas'],
                    ['left_text' => 'Ginjal', 'right_text' => 'Menyaring darah'],
                ],
                'image' => UploadedFile::fake()->image('soal.jpg', 200, 200),
            ])
        )->assertRedirect()->assertSessionHas('toast.type', 'success');

        $question->refresh();

        $this->assertSame('Soal menjodohkan yang diperbarui.', $question->body);
        $this->assertNotNull($question->image_path);
        Storage::disk('public')->assertExists($question->image_path);
        $this->assertSame(2, QuestionPair::query()->count());
        $this->assertSame(
            'Paru-paru',
            QuestionPair::query()->orderBy('order')->value('left_text')
        );
    }

    public function test_update_replaces_the_blanks_of_a_short_answer_question(): void
    {
        Storage::fake('public');

        $question = $this->storeQuestion([
            'type' => QuestionType::IsianSingkat->value,
            'options' => [],
            'blanks' => [
                ['answers' => ['klorofil'], 'score' => ''],
            ],
        ]);

        $this->put(
            route('admin.question-banks.questions.update', [
                'questionBank' => $this->questionBank->id,
                'question' => $question->id,
            ]),
            $this->payload([
                'type' => QuestionType::IsianSingkat->value,
                'options' => [],
                'blanks' => [
                    ['answers' => ['klorofil', 'Klorofil'], 'score' => ''],
                ],
                'image' => UploadedFile::fake()->image('soal.jpg', 200, 200),
            ])
        )->assertRedirect()->assertSessionHas('toast.type', 'success');

        $question->refresh();

        $this->assertNotNull($question->image_path);
        Storage::disk('public')->assertExists($question->image_path);
        $this->assertSame(1, QuestionBlank::query()->count());
        $this->assertSame(
            ['klorofil'],
            QuestionBlank::query()->firstOrFail()->answers
        );
    }

    public function test_store_creates_an_essay_question_without_answers(): void
    {
        $this->post(
            route('admin.question-banks.questions.store', $this->questionBank),
            $this->payload([
                'type' => QuestionType::Esai->value,
                'options' => [],
            ])
        )->assertRedirect();

        $question = Question::query()->firstOrFail();

        $this->assertSame(QuestionType::Esai, $question->type);
        $this->assertSame(0, QuestionOption::query()->count());
        $this->assertSame(0, QuestionPair::query()->count());
        $this->assertSame(0, QuestionBlank::query()->count());
    }

    public function test_store_rejects_a_duplicate_number_in_the_same_type(): void
    {
        $this->storeQuestion(['number' => 1]);

        $this->post(
            route('admin.question-banks.questions.store', $this->questionBank),
            $this->payload(['number' => 1])
        )->assertSessionHasErrors('number');

        $this->assertDatabaseCount('questions', 1);
    }

    public function test_store_rejects_a_number_beyond_the_bank_composition(): void
    {
        $this->post(
            route('admin.question-banks.questions.store', $this->questionBank),
            $this->payload(['number' => 9])
        )->assertSessionHasErrors('number');

        $this->assertDatabaseCount('questions', 0);
    }

    public function test_store_rejects_a_type_outside_the_bank_composition(): void
    {
        $this->questionBank->update([
            'pilihan_ganda_count' => 3,
            'pilihan_ganda_kompleks_count' => 0,
            'menjodohkan_count' => 0,
            'isian_count' => 0,
            'esai_count' => 0,
        ]);

        $this->post(
            route('admin.question-banks.questions.store', $this->questionBank),
            $this->payload([
                'type' => QuestionType::Menjodohkan->value,
                'options' => [],
                'pairs' => [
                    ['left_text' => 'Jantung', 'right_text' => 'Memompa darah'],
                    ['left_text' => 'Ginjal', 'right_text' => 'Menyaring darah'],
                ],
            ])
        )->assertSessionHasErrors('type');

        $this->assertDatabaseCount('questions', 0);
    }

    public function test_store_requires_exactly_one_correct_option(): void
    {
        $this->post(
            route('admin.question-banks.questions.store', $this->questionBank),
            $this->payload([
                'options' => [
                    ['label' => 'A', 'text' => 'Jawaban A', 'is_correct' => true],
                    ['label' => 'B', 'text' => 'Jawaban B', 'is_correct' => true],
                    ['label' => 'C', 'text' => 'Jawaban C', 'is_correct' => false],
                    ['label' => 'D', 'text' => 'Jawaban D', 'is_correct' => false],
                ],
            ])
        )->assertSessionHasErrors('options');

        $this->assertDatabaseCount('questions', 0);
    }

    public function test_store_rejects_an_option_count_that_differs_from_the_question_bank(): void
    {
        $this->post(
            route('admin.question-banks.questions.store', $this->questionBank),
            $this->payload([
                'options' => [
                    ['label' => 'A', 'text' => 'Jawaban A', 'is_correct' => true],
                    ['label' => 'B', 'text' => 'Jawaban B', 'is_correct' => false],
                ],
            ])
        )->assertSessionHasErrors('options');

        $this->assertDatabaseCount('questions', 0);
    }

    public function test_store_stores_the_question_media(): void
    {
        Storage::fake('public');

        $image = UploadedFile::fake()->image('soal.jpg', 200, 200);

        $this->post(
            route('admin.question-banks.questions.store', $this->questionBank),
            $this->payload([
                'image' => $image,
            ])
        )->assertRedirect();

        $question = Question::query()->firstOrFail();

        $this->assertNotNull($question->image_path);
        Storage::disk('public')->assertExists($question->image_path);
    }

    public function test_store_keeps_the_mathematical_formula_markup(): void
    {
        $body = '<p>Nilai <span class="math-node" data-latex="x^2 + y^2 = z^2">\\(x^2 + y^2 = z^2\\)</span>是多少?</p>';

        $this->post(
            route('admin.question-banks.questions.store', $this->questionBank),
            $this->payload(['body' => $body])
        )->assertRedirect();

        $question = Question::query()->firstOrFail();

        $this->assertSame($body, $question->body);
        $this->assertStringContainsString('data-latex="x^2 + y^2 = z^2"', $question->body);
    }

    public function test_update_changes_the_question_and_replaces_the_answers(): void
    {
        $question = $this->storeQuestion();

        $this->put(
            route('admin.question-banks.questions.update', [
                'questionBank' => $this->questionBank->id,
                'question' => $question->id,
            ]),
            $this->payload([
                'body' => 'Soal yang telah diperbarui.',
                'options' => [
                    ['label' => 'A', 'text' => 'Jawaban baru A', 'is_correct' => true],
                    ['label' => 'B', 'text' => 'Jawaban baru B', 'is_correct' => false],
                    ['label' => 'C', 'text' => 'Jawaban baru C', 'is_correct' => false],
                    ['label' => 'D', 'text' => 'Jawaban baru D', 'is_correct' => false],
                ],
            ])
        )->assertRedirect()->assertSessionHas('toast.type', 'success');

        $question->refresh();

        $this->assertSame('Soal yang telah diperbarui.', $question->body);
        $this->assertSame(4, QuestionOption::query()->count());
        $this->assertSame(
            'Jawaban baru A',
            QuestionOption::query()->where('is_correct', true)->value('text')
        );
    }

    public function test_update_keeps_the_question_media_when_no_new_file_is_uploaded(): void
    {
        Storage::fake('public');

        $question = $this->storeQuestion([
            'image' => UploadedFile::fake()->image('soal.jpg', 200, 200),
        ]);

        $path = $question->image_path;

        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);

        $this->put(
            route('admin.question-banks.questions.update', [
                'questionBank' => $this->questionBank->id,
                'question' => $question->id,
            ]),
            $this->payload(['body' => 'Soal diperbarui tanpa gambar baru.'])
        )->assertRedirect();

        $question->refresh();

        $this->assertSame($path, $question->image_path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_update_removes_the_question_media_when_requested(): void
    {
        Storage::fake('public');

        $question = $this->storeQuestion([
            'image' => UploadedFile::fake()->image('soal.jpg', 200, 200),
        ]);

        $path = $question->image_path;

        $this->put(
            route('admin.question-banks.questions.update', [
                'questionBank' => $this->questionBank->id,
                'question' => $question->id,
            ]),
            $this->payload(['remove_image' => true])
        )->assertRedirect();

        $question->refresh();

        $this->assertNull($question->image_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_update_keeps_the_option_media_when_no_new_file_is_uploaded(): void
    {
        Storage::fake('public');

        $question = $this->storeQuestion([
            'options' => [
                [
                    'label' => 'A',
                    'text' => 'Jawaban A',
                    'is_correct' => true,
                    'image' => UploadedFile::fake()->image('opsi-a.jpg', 120, 120),
                ],
                ['label' => 'B', 'text' => 'Jawaban B', 'is_correct' => false],
                ['label' => 'C', 'text' => 'Jawaban C', 'is_correct' => false],
                ['label' => 'D', 'text' => 'Jawaban D', 'is_correct' => false],
            ],
        ]);

        $option = QuestionOption::query()->where('is_correct', true)->firstOrFail();
        $path = $option->image_path;

        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);

        $this->put(
            route('admin.question-banks.questions.update', [
                'questionBank' => $this->questionBank->id,
                'question' => $question->id,
            ]),
            $this->payload([
                'body' => 'Soal diperbarui tanpa gambar opsi baru.',
                'options' => [
                    [
                        'id' => $option->id,
                        'label' => 'A',
                        'text' => 'Jawaban A',
                        'is_correct' => true,
                    ],
                    ['label' => 'B', 'text' => 'Jawaban B', 'is_correct' => false],
                    ['label' => 'C', 'text' => 'Jawaban C', 'is_correct' => false],
                    ['label' => 'D', 'text' => 'Jawaban D', 'is_correct' => false],
                ],
            ])
        )->assertRedirect();

        $this->assertSame(
            $path,
            QuestionOption::query()->where('is_correct', true)->value('image_path')
        );
        Storage::disk('public')->assertExists($path);
    }

    public function test_update_replaces_the_option_media_when_a_new_file_is_uploaded(): void
    {
        Storage::fake('public');

        $question = $this->storeQuestion([
            'options' => [
                [
                    'label' => 'A',
                    'text' => 'Jawaban A',
                    'is_correct' => true,
                    'image' => UploadedFile::fake()->image('opsi-lama.jpg', 120, 120),
                ],
                ['label' => 'B', 'text' => 'Jawaban B', 'is_correct' => false],
                ['label' => 'C', 'text' => 'Jawaban C', 'is_correct' => false],
                ['label' => 'D', 'text' => 'Jawaban D', 'is_correct' => false],
            ],
        ]);

        $option = QuestionOption::query()->where('is_correct', true)->firstOrFail();
        $oldPath = $option->image_path;

        $this->put(
            route('admin.question-banks.questions.update', [
                'questionBank' => $this->questionBank->id,
                'question' => $question->id,
            ]),
            $this->payload([
                'options' => [
                    [
                        'id' => $option->id,
                        'label' => 'A',
                        'text' => 'Jawaban A',
                        'is_correct' => true,
                        'image' => UploadedFile::fake()->image('opsi-baru.jpg', 200, 200),
                    ],
                    ['label' => 'B', 'text' => 'Jawaban B', 'is_correct' => false],
                    ['label' => 'C', 'text' => 'Jawaban C', 'is_correct' => false],
                    ['label' => 'D', 'text' => 'Jawaban D', 'is_correct' => false],
                ],
            ])
        )->assertRedirect();

        $newPath = QuestionOption::query()->where('is_correct', true)->value('image_path');

        $this->assertNotSame($oldPath, $newPath);
        Storage::disk('public')->assertExists($newPath);
        Storage::disk('public')->assertMissing($oldPath);
    }

    public function test_update_removes_the_option_media_when_requested(): void
    {
        Storage::fake('public');

        $question = $this->storeQuestion([
            'options' => [
                [
                    'label' => 'A',
                    'text' => 'Jawaban A',
                    'is_correct' => true,
                    'image' => UploadedFile::fake()->image('opsi-a.jpg', 120, 120),
                ],
                ['label' => 'B', 'text' => 'Jawaban B', 'is_correct' => false],
                ['label' => 'C', 'text' => 'Jawaban C', 'is_correct' => false],
                ['label' => 'D', 'text' => 'Jawaban D', 'is_correct' => false],
            ],
        ]);

        $option = QuestionOption::query()->where('is_correct', true)->firstOrFail();
        $path = $option->image_path;

        $this->put(
            route('admin.question-banks.questions.update', [
                'questionBank' => $this->questionBank->id,
                'question' => $question->id,
            ]),
            $this->payload([
                'options' => [
                    [
                        'id' => $option->id,
                        'label' => 'A',
                        'text' => 'Jawaban A',
                        'is_correct' => true,
                        'remove_image' => true,
                    ],
                    ['label' => 'B', 'text' => 'Jawaban B', 'is_correct' => false],
                    ['label' => 'C', 'text' => 'Jawaban C', 'is_correct' => false],
                    ['label' => 'D', 'text' => 'Jawaban D', 'is_correct' => false],
                ],
            ])
        )->assertRedirect();

        $this->assertNull(
            QuestionOption::query()->where('is_correct', true)->value('image_path')
        );
        Storage::disk('public')->assertMissing($path);
    }

    public function test_update_deletes_the_media_of_removed_options(): void
    {
        Storage::fake('public');

        $question = $this->storeQuestion([
            'options' => [
                [
                    'label' => 'A',
                    'text' => 'Jawaban A',
                    'is_correct' => true,
                    'image' => UploadedFile::fake()->image('opsi-a.jpg', 120, 120),
                ],
                ['label' => 'B', 'text' => 'Jawaban B', 'is_correct' => false],
                ['label' => 'C', 'text' => 'Jawaban C', 'is_correct' => false],
                ['label' => 'D', 'text' => 'Jawaban D', 'is_correct' => false],
            ],
        ]);

        $path = QuestionOption::query()->where('is_correct', true)->value('image_path');

        $this->put(
            route('admin.question-banks.questions.update', [
                'questionBank' => $this->questionBank->id,
                'question' => $question->id,
            ]),
            $this->payload([
                'options' => [
                    ['label' => 'A', 'text' => 'Jawaban A', 'is_correct' => true],
                    ['label' => 'B', 'text' => 'Jawaban B', 'is_correct' => false],
                    ['label' => 'C', 'text' => 'Jawaban C', 'is_correct' => false],
                    ['label' => 'D', 'text' => 'Jawaban D', 'is_correct' => false],
                ],
            ])
        )->assertRedirect();

        $this->assertNull(
            QuestionOption::query()->where('is_correct', true)->value('image_path')
        );
        Storage::disk('public')->assertMissing($path);
    }

    public function test_update_ignores_option_media_belonging_to_another_question(): void
    {
        Storage::fake('public');

        $otherQuestion = $this->storeQuestion([
            'options' => [
                [
                    'label' => 'A',
                    'text' => 'Jawaban A',
                    'is_correct' => true,
                    'image' => UploadedFile::fake()->image('opsi-a.jpg', 120, 120),
                ],
                ['label' => 'B', 'text' => 'Jawaban B', 'is_correct' => false],
                ['label' => 'C', 'text' => 'Jawaban C', 'is_correct' => false],
                ['label' => 'D', 'text' => 'Jawaban D', 'is_correct' => false],
            ],
        ]);

        $otherOption = QuestionOption::query()
            ->where('question_id', $otherQuestion->id)
            ->where('is_correct', true)
            ->firstOrFail();

        $question = $this->storeQuestion(['number' => 2]);

        $this->put(
            route('admin.question-banks.questions.update', [
                'questionBank' => $this->questionBank->id,
                'question' => $question->id,
            ]),
            $this->payload([
                'number' => 2,
                'options' => [
                    [
                        'id' => $otherOption->id,
                        'label' => 'A',
                        'text' => 'Jawaban A',
                        'is_correct' => true,
                    ],
                    ['label' => 'B', 'text' => 'Jawaban B', 'is_correct' => false],
                    ['label' => 'C', 'text' => 'Jawaban C', 'is_correct' => false],
                    ['label' => 'D', 'text' => 'Jawaban D', 'is_correct' => false],
                ],
            ])
        )->assertRedirect();

        $this->assertNull(
            QuestionOption::query()
                ->where('question_id', $question->id)
                ->where('is_correct', true)
                ->value('image_path')
        );
    }

    public function test_update_accepts_a_post_request_with_a_spoofed_method_and_a_file(): void
    {
        Storage::fake('public');

        $question = $this->storeQuestion();

        $this->post(
            route('admin.question-banks.questions.update', [
                'questionBank' => $this->questionBank->id,
                'question' => $question->id,
            ]),
            $this->payload([
                '_method' => 'PUT',
                'body' => 'Soal diperbarui lewat multipart.',
                'image' => UploadedFile::fake()->image('soal.jpg', 200, 200),
            ])
        )->assertRedirect()->assertSessionHas('toast.type', 'success');

        $question->refresh();

        $this->assertNotNull($question->image_path);
        Storage::disk('public')->assertExists($question->image_path);
    }

    public function test_store_uses_the_question_bank_score(): void
    {
        $this->questionBank->update([
            'pilihan_ganda_score' => 75,
        ]);

        $this->storeQuestion();

        $this->assertSame(75.0, Question::query()->firstOrFail()->score);
    }

    public function test_update_applies_the_current_question_bank_score(): void
    {
        $question = $this->storeQuestion();

        $this->questionBank->update(['pilihan_ganda_score' => 80]);

        $this->put(
            route('admin.question-banks.questions.update', [
                'questionBank' => $this->questionBank->id,
                'question' => $question->id,
            ]),
            $this->payload()
        )->assertRedirect();

        $this->assertSame(80.0, $question->refresh()->score);
    }

    public function test_store_follows_the_option_count_of_the_question_bank(): void
    {
        $this->questionBank->update(['pilihan_ganda_option_count' => 3]);

        $this->post(
            route('admin.question-banks.questions.store', $this->questionBank),
            $this->payload([
                'options' => [
                    ['label' => 'A', 'text' => 'Jawaban A', 'is_correct' => true],
                    ['label' => 'B', 'text' => 'Jawaban B', 'is_correct' => false],
                    ['label' => 'C', 'text' => 'Jawaban C', 'is_correct' => false],
                ],
            ])
        )->assertRedirect();

        $this->assertSame(3, QuestionOption::query()->count());
    }

    public function test_store_rejects_a_question_from_another_bank(): void
    {
        $otherBank = QuestionBank::factory()->create([
            'school_id' => $this->school->id,
        ]);
        $question = Question::factory()->create([
            'question_bank_id' => $otherBank->id,
            'school_id' => $this->school->id,
        ]);

        $this->put(
            route('admin.question-banks.questions.update', [
                'questionBank' => $this->questionBank->id,
                'question' => $question->id,
            ]),
            $this->payload()
        )->assertNotFound();
    }

    public function test_destroy_all_removes_every_question_of_the_bank(): void
    {
        Storage::fake('public');

        $this->storeQuestion([
            'options' => [
                [
                    'label' => 'A',
                    'text' => 'Jawaban A',
                    'is_correct' => true,
                    'image' => UploadedFile::fake()->image('opsi.jpg', 120, 120),
                ],
                ['label' => 'B', 'text' => 'Jawaban B', 'is_correct' => false],
                ['label' => 'C', 'text' => 'Jawaban C', 'is_correct' => false],
                ['label' => 'D', 'text' => 'Jawaban D', 'is_correct' => false],
            ],
            'image' => UploadedFile::fake()->image('soal.jpg', 200, 200),
        ]);

        $this->storeQuestion([
            'type' => QuestionType::Menjodohkan->value,
            'number' => 1,
            'options' => [],
            'pairs' => [
                ['left_text' => 'Jantung', 'right_text' => 'Memompa darah'],
                ['left_text' => 'Ginjal', 'right_text' => 'Menyaring darah'],
            ],
        ]);

        $question = Question::query()->firstOrFail();
        $option = QuestionOption::query()->firstOrFail();

        $questionPath = $question->image_path;
        $optionPath = $option->image_path;

        $this->delete(route('admin.question-banks.questions.destroy-all', $this->questionBank))
            ->assertRedirect()
            ->assertSessionHas('toast.type', 'success');

        $this->assertSame(0, Question::query()->count());
        $this->assertSame(0, QuestionOption::query()->count());
        $this->assertSame(0, QuestionPair::query()->count());

        Storage::disk('public')->assertMissing($questionPath);
        Storage::disk('public')->assertMissing($optionPath);
    }

    public function test_destroy_all_keeps_the_questions_of_other_banks(): void
    {
        $otherBank = QuestionBank::factory()->create([
            'school_id' => $this->school->id,
        ]);

        $otherQuestion = Question::factory()->create([
            'question_bank_id' => $otherBank->id,
            'school_id' => $this->school->id,
        ]);

        $this->storeQuestion();

        $this->delete(route('admin.question-banks.questions.destroy-all', $this->questionBank))
            ->assertRedirect();

        $this->assertSame(1, Question::query()->count());
        $this->assertTrue(Question::query()->whereKey($otherQuestion->id)->exists());
    }

    public function test_destroy_all_reports_when_the_bank_has_no_questions(): void
    {
        $this->delete(route('admin.question-banks.questions.destroy-all', $this->questionBank))
            ->assertRedirect()
            ->assertSessionHas('toast.type', 'error');

        $this->assertSame(0, Question::query()->count());
    }

    public function test_destroy_all_forbids_regular_guru(): void
    {
        $this->storeQuestion();

        $this->actingAs(User::factory()->guru()->create())
            ->delete(route('admin.question-banks.questions.destroy-all', $this->questionBank))
            ->assertForbidden();

        $this->assertSame(1, Question::query()->count());
    }

    public function test_destroy_deletes_the_question(): void
    {
        $question = $this->storeQuestion();

        $this->delete(
            route('admin.question-banks.questions.destroy', [
                'questionBank' => $this->questionBank->id,
                'question' => $question->id,
            ])
        )->assertRedirect()->assertSessionHas('toast.type', 'success');

        $this->assertDatabaseMissing('questions', ['id' => $question->id]);
        $this->assertDatabaseMissing('question_options', ['question_id' => $question->id]);
    }

    public function test_headmaster_can_view_but_cannot_manage_questions(): void
    {
        $headmaster = User::factory()->guru()->create();
        $headmasterSchool = School::factory()->create(['headmaster_id' => $headmaster->id]);
        $questionBank = QuestionBank::factory()->create([
            'school_id' => $headmasterSchool->id,
        ]);

        $this->actingAs($headmaster)
            ->get(route('admin.question-banks.questions.index', $questionBank))
            ->assertOk();

        $this->actingAs($headmaster)
            ->post(
                route('admin.question-banks.questions.store', $questionBank),
                $this->payload()
            )
            ->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function storeQuestion(array $overrides = []): Question
    {
        $this->post(
            route('admin.question-banks.questions.store', $this->questionBank),
            $this->payload($overrides)
        )->assertRedirect();

        return Question::query()->latest('id')->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'number' => 1,
            'type' => QuestionType::PilihanGanda->value,
            'body' => 'Contoh soal pilihan ganda.',
            'is_active' => true,
            'options' => [
                ['label' => 'A', 'text' => 'Jawaban A', 'is_correct' => true],
                ['label' => 'B', 'text' => 'Jawaban B', 'is_correct' => false],
                ['label' => 'C', 'text' => 'Jawaban C', 'is_correct' => false],
                ['label' => 'D', 'text' => 'Jawaban D', 'is_correct' => false],
            ],
            'pairs' => [],
            'blanks' => [],
        ], $overrides);
    }
}
