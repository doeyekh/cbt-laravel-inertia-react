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
        Schema::create('exam_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('exam_participant_groups')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('session_id')
                ->nullable()
                ->constrained('exam_sessions')
                ->nullOnDelete();
            $table->foreignId('room_id')
                ->nullable()
                ->constrained('rooms')
                ->nullOnDelete();
            $table->string('username', 50)->nullable();
            $table->string('participant_number', 30)->nullable();
            $table->string('password')->nullable();
            $table->text('password_plain')->nullable();
            $table->timestamps();

            $table->unique(['group_id', 'username']);
            $table->unique(['group_id', 'participant_number']);
            $table->index(['student_id', 'school_id']);
            $table->index(['session_id', 'room_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_participants');
    }
};
