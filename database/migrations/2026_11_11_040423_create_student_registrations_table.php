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
        Schema::create('student_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('nis', 30);
            $table->foreignId('entry_academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->string('status', 20)->default('aktif')->index();
            $table->date('registered_at');
            $table->timestamps();

            $table->unique(['student_id', 'school_id']);
            $table->unique(['school_id', 'nis']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_registrations');
    }
};
