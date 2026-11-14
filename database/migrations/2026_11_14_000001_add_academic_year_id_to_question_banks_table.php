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
        Schema::table('question_banks', function (Blueprint $table) {
            $table->foreignId('academic_year_id')
                ->nullable()
                ->after('subject_id')
                ->constrained('academic_years')
                ->nullOnDelete();
        });

        $activeAcademicYearId = DB::table('academic_years')
            ->where('is_active', true)
            ->orderByDesc('code')
            ->value('id');

        if ($activeAcademicYearId !== null) {
            DB::table('question_banks')
                ->whereNull('academic_year_id')
                ->update(['academic_year_id' => $activeAcademicYearId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('question_banks', function (Blueprint $table) {
            $table->dropForeign(['academic_year_id']);
            $table->dropColumn('academic_year_id');
        });
    }
};
