<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Catatan kehadiran peserta per jadwal ujian. Setiap peserta pada
     * jadwal yang sama hanya dapat memiliki satu catatan kehadiran.
     */
    public function up(): void
    {
        Schema::create('exam_attendances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('exam_schedule_id')
                ->constrained('exam_schedules')
                ->cascadeOnDelete();
            $table->foreignId('exam_participant_id')
                ->constrained('exam_participants')
                ->cascadeOnDelete();
            $table->string('status', 20)->default('tidak_hadir');
            $table->timestamp('check_in_time')->nullable();
            $table->timestamps();

            $table->unique(['exam_schedule_id', 'exam_participant_id']);
            $table->index(['exam_schedule_id', 'status']);
            $table->index('exam_participant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_attendances');
    }
};
