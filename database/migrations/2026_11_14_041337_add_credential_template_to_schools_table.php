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
        Schema::table('schools', function (Blueprint $table): void {
            $table->string('username_template', 120)->default('{nis}');
            $table->string('participant_number_template', 120)->default('{ruang}-{no}');
            $table->unsignedTinyInteger('number_width')->default(3);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table): void {
            $table->dropColumn([
                'username_template',
                'participant_number_template',
                'number_width',
            ]);
        });
    }
};
