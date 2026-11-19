<?php

namespace Tests\Feature\Exports;

use App\Enums\ExamAttemptStatus;
use App\Exports\ExamReportExport;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamReportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_maps_correct_wrong_and_ungraded_answer_counts(): void
    {
        $attempt = ExamAttempt::factory()->create([
            'status' => ExamAttemptStatus::Graded,
            'score' => 80,
            'max_score' => 100,
        ]);
        $questions = Question::factory()->count(4)->create();

        foreach ([true, true, false, null] as $index => $isCorrect) {
            ExamAnswer::factory()->create([
                'exam_attempt_id' => $attempt->id,
                'question_id' => $questions[$index]->id,
                'is_correct' => $isCorrect,
            ]);
        }

        $attempt = ExamAttempt::query()
            ->with(['answers', 'participant.student'])
            ->findOrFail($attempt->id);
        $export = new ExamReportExport(collect([$attempt]));

        $row = array_combine($export->headings(), $export->map($attempt));

        $this->assertSame(2, $row['Jumlah Benar']);
        $this->assertSame(1, $row['Jumlah Salah']);
        $this->assertSame(1, $row['Belum Dinilai']);
        $this->assertSame(80.0, $row['Skor']);
        $this->assertSame(100.0, $row['Skor Maksimum']);
        $this->assertSame(80.0, $row['Nilai Akhir (0-100)']);
    }
}
