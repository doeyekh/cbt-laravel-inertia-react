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
        Schema::create('question_banks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('assessment_type_id')->nullable()->constrained('assessment_types')->nullOnDelete();
            $table->string('code', 30);
            $table->string('name', 150);
            $table->string('description', 500)->nullable();
            $table->unsignedSmallInteger('pilihan_ganda_count')->default(0);
            $table->unsignedSmallInteger('pilihan_ganda_kompleks_count')->default(0);
            $table->unsignedSmallInteger('menjodohkan_count')->default(0);
            $table->unsignedSmallInteger('isian_count')->default(0);
            $table->unsignedSmallInteger('esai_count')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['school_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_banks');
    }
};
