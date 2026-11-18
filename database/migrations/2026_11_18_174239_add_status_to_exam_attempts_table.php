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
     * Status attempt menjadi sumber kebenaran untuk membedakan ujian yang
     * masih dikerjakan dan yang sudah dikumpulkan. Sebelum kolom ini ada,
     * setiap autosave mengisi `submitted_at`, sehingga nilai tersebut tidak
     * dapat dipercaya sebagai penanda pengumpulan. Karena itu baris lama
     * dikembalikan ke status `in_progress` dengan `submitted_at` dikosongkan.
     */
    public function up(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->string('status', 20)->default('in_progress')->after('exam_package_id');
            $table->index(['exam_schedule_id', 'status']);
        });

        DB::table('exam_attempts')
            ->whereNotNull('submitted_at')
            ->update([
                'status' => 'in_progress',
                'submitted_at' => null,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropIndex(['exam_schedule_id', 'status']);
            $table->dropColumn('status');
        });
    }
};
