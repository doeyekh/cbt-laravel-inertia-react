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
        Schema::table('questions', function (Blueprint $table) {
            $table->foreignId('question_bank_id')->nullable()->constrained('question_banks')->nullOnDelete();
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex(['difficulty']);
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('difficulty');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->string('difficulty', 20)->default('sedang')->index();
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropForeign(['question_bank_id']);
            $table->dropColumn('question_bank_id');
        });
    }
};
