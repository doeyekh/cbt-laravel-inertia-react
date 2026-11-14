<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('question_banks', function (Blueprint $table) {
            $table->decimal('pilihan_ganda_score', 5, 2)->default(50)->after('pilihan_ganda_count');
            $table->decimal('pilihan_ganda_kompleks_score', 5, 2)->default(30)->after('pilihan_ganda_kompleks_count');
            $table->decimal('menjodohkan_score', 5, 2)->default(40)->after('menjodohkan_count');
            $table->decimal('isian_score', 5, 2)->default(20)->after('isian_count');
            $table->decimal('esai_score', 5, 2)->default(10)->after('esai_count');
            $table->unsignedSmallInteger('pilihan_ganda_option_count')->default(4)->after('pilihan_ganda_score');
            $table->unsignedSmallInteger('pilihan_ganda_kompleks_option_count')->default(4)->after('pilihan_ganda_kompleks_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('question_banks', function (Blueprint $table) {
            $table->dropColumn([
                'pilihan_ganda_score',
                'pilihan_ganda_kompleks_score',
                'menjodohkan_score',
                'isian_score',
                'esai_score',
                'pilihan_ganda_option_count',
                'pilihan_ganda_kompleks_option_count',
            ]);
        });
    }
};
