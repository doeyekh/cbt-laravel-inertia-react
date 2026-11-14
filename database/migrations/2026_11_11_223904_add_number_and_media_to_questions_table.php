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
        Schema::table('questions', function (Blueprint $table) {
            $table->unsignedSmallInteger('number')->nullable()->after('type');
            $table->string('image_path')->nullable()->after('explanation');
            $table->string('audio_path')->nullable()->after('image_path');
        });

        DB::table('questions')
            ->orderBy('id')
            ->get(['id', 'question_bank_id', 'type'])
            ->groupBy(fn ($question): string => $question->question_bank_id.'-'.$question->type)
            ->each(function ($questions): void {
                $number = 0;

                foreach ($questions as $question) {
                    $number++;

                    DB::table('questions')
                        ->where('id', $question->id)
                        ->update(['number' => $number]);
                }
            });

        Schema::table('questions', function (Blueprint $table) {
            $table->unsignedSmallInteger('number')->default(0)->change();
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->unique(['question_bank_id', 'type', 'number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropUnique(['question_bank_id', 'type', 'number']);
            $table->dropColumn(['number', 'image_path', 'audio_path']);
        });
    }
};
