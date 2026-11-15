<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('exam_schedules', function (Blueprint $table) {
            $table->foreignId('session_id')->nullable()->change();
            $table->foreignId('room_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $hasNullableReferences = DB::table('exam_schedules')
            ->whereNull('session_id')
            ->orWhereNull('room_id')
            ->exists();

        if ($hasNullableReferences) {
            throw new RuntimeException(
                'Cannot make exam schedule session and room references required while null values exist.'
            );
        }

        Schema::table('exam_schedules', function (Blueprint $table) {
            $table->foreignId('session_id')->nullable(false)->change();
            $table->foreignId('room_id')->nullable(false)->change();
        });
    }
};
