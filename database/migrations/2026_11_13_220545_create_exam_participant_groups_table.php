<?php

namespace Database\Migrations;

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
        Schema::create('exam_participant_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name', 150);
            $table->boolean('is_active')->default(true);
            $table->string('username_template', 120)->default('{nis}');
            $table->string('participant_number_template', 120)->default('{ruang}-{no}');
            $table->unsignedTinyInteger('number_width')->default(2);
            $table->timestamps();

            $table->unique(['school_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_participant_groups');
    }
};
