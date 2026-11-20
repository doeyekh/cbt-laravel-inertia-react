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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_participant_group_classes');
    }
};
