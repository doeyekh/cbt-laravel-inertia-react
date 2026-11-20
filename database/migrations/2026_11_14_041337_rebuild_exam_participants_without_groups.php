<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Entitas grup peserta dihapus. Seluruh data peserta lama ikut dibuang
     * sesuai keputusan "mulai bersih".
     */
    public function up(): void
    {
        Schema::dropIfExists('exam_participant_group_classes');
        Schema::dropIfExists('exam_participants');
        Schema::dropIfExists('exam_participant_groups');

        Schema::create('exam_participants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
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

            $table->unique(['student_id', 'school_id', 'academic_year_id']);
            $table->unique(['school_id', 'academic_year_id', 'username']);
            $table->unique(
                ['school_id', 'academic_year_id', 'participant_number'],
                'exam_participants_school_year_number_unique'
            );
            $table->index(['session_id', 'room_id']);
            $table->index(['student_id', 'school_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_participants');

        Schema::create('exam_participant_groups', function (Blueprint $table): void {
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

        Schema::create('exam_participants', function (Blueprint $table): void {
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

        Schema::create('exam_participant_group_classes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('exam_participant_group_id')
                ->constrained('exam_participant_groups')
                ->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(
                ['exam_participant_group_id', 'class_id'],
                'exam_participant_group_class_unique'
            );
        });
    }
};
