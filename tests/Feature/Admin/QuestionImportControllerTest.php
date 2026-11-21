<?php

namespace Tests\Feature\Admin;

use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionOption;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\SubjectTeacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class QuestionImportControllerTest extends TestCase
{
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
            'pilihan_ganda_option_count' => 4,
            'pilihan_ganda_kompleks_option_count' => 4,
        ]);
    }

    public function test_index_returns_the_import_page(): void
    {
        $this->get(route('admin.question-banks.questions.import', $this->questionBank))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Questions/Import')
                ->where('questionBank.id', $this->questionBank->id)
                ->has('types', 5)
            );
    }

    public function test_index_forbids_regular_guru(): void
    {
        $this->actingAs(User::factory()->guru()->create())
            ->get(route('admin.question-banks.questions.import', $this->questionBank))
            ->assertForbidden();
    }

    public function test_template_downloads_an_excel_file(): void
    {
        $response = $this->get(route('admin.question-banks.questions.import.template', $this->questionBank));

        $response->assertOk();
        $response->assertHeader(
            'content-type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );

        $this->assertStringContainsString(
            'template-import-soal-'.$this->questionBank->code.'.xlsx',
            $response->headers->get('content-disposition') ?? ''
        );
    }

    public function test_store_imports_questions_of_every_type(): void
    {
        $this->post(
            route('admin.question-banks.questions.import.store', $this->questionBank),
            [
                'file' => $this->spreadsheet([
                    ['pilihan_ganda', 1, 'Soal PG', 'A', 'B', 'C', 'D', '', 'A', '', ''],
                    ['pilihan_ganda_kompleks', 1, 'Soal PK', 'A', 'B', 'C', 'D', '', 'A,C', '', ''],
                    ['menjodohkan', 1, 'Soal MJ', '', '', '', '', '', '', 'Jantung=Memompa darah; Ginjal=Menyaring darah', ''],
                    ['isian_singkat', 1, 'Soal IS', '', '', '', '', '', '', '', 'klorofil; Klorofil'],
                    ['esai', 1, 'Soal ES', '', '', '', '', '', '', '', '', ''],
                ]),
            ]
        )->assertRedirect()->assertSessionHas('toast.type', 'success');

        $this->assertSame(5, Question::query()->count());

        $multipleChoice = Question::query()->where('type', QuestionType::PilihanGanda->value)->firstOrFail();

        $this->assertSame('<p>Soal PG</p>', $multipleChoice->body);
        $this->assertSame(50.0, $multipleChoice->score);
        $this->assertSame(4, $multipleChoice->options()->count());
        $this->assertSame(
            ['A'],
            $multipleChoice->options()->where('is_correct', true)->pluck('label')->all()
        );

        $complex = Question::query()->where('type', QuestionType::PilihanGandaKompleks->value)->firstOrFail();

        $this->assertSame(30.0, $complex->score);
        $this->assertSame(
            ['A', 'C'],
            $complex->options()->where('is_correct', true)->orderBy('order')->pluck('label')->all()
        );
        $this->assertSame(
            15.0,
            (float) $complex->options()->where('is_correct', true)->firstOrFail()->score
        );

        $matching = Question::query()->where('type', QuestionType::Menjodohkan->value)->firstOrFail();

        $this->assertSame(2, $matching->pairs()->count());
        $this->assertSame('Memompa darah', $matching->pairs()->firstOrFail()->right_text);

        $shortAnswer = Question::query()->where('type', QuestionType::IsianSingkat->value)->firstOrFail();

        $this->assertSame(['klorofil'], $shortAnswer->blanks()->firstOrFail()->answers);
        $this->assertSame(20.0, (float) $shortAnswer->blanks()->firstOrFail()->score);

        $this->assertSame(8, QuestionOption::query()->count());
    }

    public function test_store_skips_invalid_rows(): void
    {
        $this->post(
            route('admin.question-banks.questions.import.store', $this->questionBank),
            [
                'file' => $this->spreadsheet([
                    ['pilihan_ganda', 1, 'Soal PG', 'A', 'B', 'C', 'D', '', 'A', '', ''],
                    ['tipe_ngawur', 1, 'Tipe tidak dikenal', '', '', '', '', '', '', '', '', ''],
                    ['pilihan_ganda', 9, 'Nomor di luar komposisi', '', '', '', '', '', '', '', '', ''],
                    ['pilihan_ganda', 2, 'Kunci tidak sesuai', 'A', 'B', 'C', 'D', '', 'Z', '', ''],
                    ['pilihan_ganda', 3, 'Opsi kurang', 'A', 'B', '', '', '', '', '', '', ''],
                ]),
            ]
        )->assertRedirect();

        $this->assertSame(1, Question::query()->count());
        $this->assertSame(
            '<p>Soal PG</p>',
            Question::query()->firstOrFail()->body
        );
    }

    public function test_store_skips_duplicate_numbers(): void
    {
        $this->post(
            route('admin.question-banks.questions.import.store', $this->questionBank),
            [
                'file' => $this->spreadsheet([
                    ['pilihan_ganda', 1, 'Soal PG', 'A', 'B', 'C', 'D', '', 'A', '', ''],
                ]),
            ]
        )->assertRedirect();

        $this->post(
            route('admin.question-banks.questions.import.store', $this->questionBank),
            [
                'file' => $this->spreadsheet([
                    ['pilihan_ganda', 1, 'Soal PG kembar', 'A', 'B', 'C', 'D', '', 'A', '', ''],
                ]),
            ]
        )->assertRedirect()->assertSessionHas('toast.type', 'error');

        $this->assertSame(1, Question::query()->count());
    }

    public function test_store_requires_a_file(): void
    {
        $this->post(
            route('admin.question-banks.questions.import.store', $this->questionBank),
            []
        )->assertSessionHasErrors('file');

        $this->assertSame(0, Question::query()->count());
    }

    public function test_guru_can_import_questions_of_a_taught_subject(): void
    {
        $guru = User::factory()->guru()->create(['school_id' => $this->school->id]);
        $schoolClass = SchoolClass::factory()->create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->questionBank->academic_year_id,
        ]);

        SubjectTeacher::factory()
            ->forClass($schoolClass, null, $this->subject, $guru)
            ->create([
                'academic_year_id' => $this->questionBank->academic_year_id,
                'is_active' => true,
            ]);

        $this->actingAs($guru)
            ->get(route('admin.question-banks.questions.import', $this->questionBank))
            ->assertOk();

        $this->actingAs($guru)
            ->post(
                route('admin.question-banks.questions.import.store', $this->questionBank),
                [
                    'file' => $this->spreadsheet([
                        ['pilihan_ganda', 1, 'Soal PG', 'A', 'B', 'C', 'D', '', 'A', '', ''],
                    ]),
                ]
            )->assertRedirect()->assertSessionHas('toast.type', 'success');

        $this->assertSame(1, Question::query()->count());
    }

    public function test_store_forbids_regular_guru(): void
    {
        $this->actingAs(User::factory()->guru()->create())
            ->post(
                route('admin.question-banks.questions.import.store', $this->questionBank),
                ['file' => $this->spreadsheet([])]
            )
            ->assertForbidden();

        $this->assertSame(0, Question::query()->count());
    }

    /**
     * Build an uploaded spreadsheet with the question import headers.
     *
     * @param  list<list<string|int>>  $rows
     */
    private function spreadsheet(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Soal');

        $sheet->fromArray([[
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
        ]], null, 'A1');

        if ($rows !== []) {
            $sheet->fromArray($rows, null, 'A2');
        }

        $path = tempnam(sys_get_temp_dir(), 'import-soal').'.xlsx';

        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, 'soal.xlsx', null, null, true);
    }
}
