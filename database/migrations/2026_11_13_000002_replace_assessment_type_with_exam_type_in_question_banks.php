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
            $table->foreignId('exam_type_id')->nullable()->constrained('exam_types')->nullOnDelete()->after('subject_id');
            $table->dropForeign(['assessment_type_id']);
            $table->dropColumn('assessment_type_id');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->foreignId('exam_type_id')->nullable()->constrained('exam_types')->nullOnDelete()->after('subject_id');
            $table->dropForeign(['assessment_type_id']);
            $table->dropColumn('assessment_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->foreignId('assessment_type_id')->nullable()->constrained('assessment_types')->nullOnDelete()->after('subject_id');
            $table->dropForeign(['exam_type_id']);
            $table->dropColumn('exam_type_id');
        });

        Schema::table('question_banks', function (Blueprint $table) {
            $table->foreignId('assessment_type_id')->nullable()->constrained('assessment_types')->nullOnDelete()->after('subject_id');
            $table->dropForeign(['exam_type_id']);
            $table->dropColumn('exam_type_id');
        });
    }
};
