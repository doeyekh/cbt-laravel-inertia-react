<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Penanda "ragu-ragu" dipisahkan dari jawaban agar menandai soal tidak
     * menghapus jawaban yang sudah diisi. Baris lama yang menyimpan tanda
     * tersebut di kolom `answer` dipindahkan ke `is_flagged`.
     */
    public function up(): void
    {
        Schema::table('exam_answers', function (Blueprint $table) {
            $table->boolean('is_flagged')->default(false)->after('answer');
        });

        DB::table('exam_answers')
            ->where('answer', 'flagged')
            ->update([
                'is_flagged' => true,
                'answer' => null,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('exam_answers')
            ->where('is_flagged', true)
            ->whereNull('answer')
            ->update(['answer' => 'flagged']);

        Schema::table('exam_answers', function (Blueprint $table) {
            $table->dropColumn('is_flagged');
        });
    }
};
