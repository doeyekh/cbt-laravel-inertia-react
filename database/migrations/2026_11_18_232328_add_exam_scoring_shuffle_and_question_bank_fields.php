<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table): void {
            $table->decimal('score', 8, 2)->nullable();
            $table->decimal('max_score', 8, 2)->nullable();
            $table->timestamp('graded_at')->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('question_order')->nullable();
            $table->json('option_order')->nullable();
        });

        Schema::table('exam_answers', function (Blueprint $table): void {
            $table->boolean('is_correct')->nullable();
            $table->decimal('score_awarded', 8, 2)->nullable();
        });

        Schema::table('exam_schedules', function (Blueprint $table): void {
            $table->foreignId('question_bank_id')
                ->nullable()
                ->constrained('question_banks')
                ->nullOnDelete();
        });

        DB::table('exam_schedules')
            ->select(['id', 'exam_package_id', 'subject_id'])
            ->orderBy('id')
            ->chunkById(100, function ($schedules): void {
                foreach ($schedules as $schedule) {
                    $package = DB::table('exam_packages')
                        ->where('id', $schedule->exam_package_id)
                        ->first(['school_id', 'academic_year_id', 'exam_type_id']);

                    if ($package === null) {
                        continue;
                    }

                    $matchingBankIds = DB::table('question_banks')
                        ->where('school_id', $package->school_id)
                        ->where('academic_year_id', $package->academic_year_id)
                        ->where('exam_type_id', $package->exam_type_id)
                        ->where('subject_id', $schedule->subject_id)
                        ->limit(2)
                        ->pluck('id');

                    if ($matchingBankIds->count() === 1) {
                        DB::table('exam_schedules')
                            ->where('id', $schedule->id)
                            ->update(['question_bank_id' => $matchingBankIds->first()]);
                    }
                }
            });

        DB::table('exam_answers')
            ->join('questions', 'questions.id', '=', 'exam_answers.question_id')
            ->whereIn('questions.type', ['pilihan_ganda', 'pilihan_ganda_kompleks'])
            ->select(['exam_answers.id', 'exam_answers.question_id', 'exam_answers.answer', 'questions.type'])
            ->orderBy('exam_answers.id')
            ->chunkById(100, function ($answers): void {
                foreach ($answers as $answer) {
                    if (! is_string($answer->answer) || $answer->answer === '') {
                        continue;
                    }

                    $isArrayAnswer = str_starts_with($answer->answer, '__array__:');
                    $selectedValues = $isArrayAnswer
                        ? json_decode(substr($answer->answer, strlen('__array__:')), true)
                        : [$answer->answer];

                    if (! is_array($selectedValues)) {
                        continue;
                    }

                    $optionIds = [];
                    $allValuesMatched = true;

                    foreach ($selectedValues as $selectedValue) {
                        $optionQuery = DB::table('question_options')
                            ->where('question_id', $answer->question_id);

                        if (is_string($selectedValue) && ctype_digit($selectedValue)) {
                            $matchingOption = (clone $optionQuery)
                                ->where('id', (int) $selectedValue)
                                ->first(['id']);
                        } else {
                            $matchingOptions = (clone $optionQuery)
                                ->where(function ($query) use ($selectedValue): void {
                                    $query->where('text', $selectedValue)
                                        ->orWhere('label', $selectedValue);
                                })
                                ->limit(2)
                                ->pluck('id');
                            $matchingOption = $matchingOptions->count() === 1
                                ? (object) ['id' => $matchingOptions->first()]
                                : null;
                        }

                        if ($matchingOption === null) {
                            $allValuesMatched = false;

                            break;
                        }

                        $optionIds[] = (string) $matchingOption->id;
                    }

                    if ($allValuesMatched) {
                        $storedAnswer = $answer->type === 'pilihan_ganda_kompleks'
                            ? '__array__:'.json_encode($optionIds, JSON_THROW_ON_ERROR)
                            : ($optionIds[0] ?? null);

                        DB::table('exam_answers')
                            ->where('id', $answer->id)
                            ->update(['answer' => $storedAnswer]);
                    }
                }
            }, 'exam_answers.id', 'id');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_schedules', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('question_bank_id');
        });

        Schema::table('exam_answers', function (Blueprint $table): void {
            $table->dropColumn(['is_correct', 'score_awarded']);
        });

        Schema::table('exam_attempts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('graded_by');
            $table->dropColumn([
                'score',
                'max_score',
                'graded_at',
                'question_order',
                'option_order',
            ]);
        });
    }
};
