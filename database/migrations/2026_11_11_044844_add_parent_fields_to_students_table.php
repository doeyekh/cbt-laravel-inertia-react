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
        Schema::table('students', function (Blueprint $table) {
            $table->string('father_name')->nullable();
            $table->string('father_occupation')->nullable();
            $table->string('father_phone', 30)->nullable();
            $table->string('mother_name')->nullable();
            $table->string('mother_occupation')->nullable();
            $table->string('mother_phone', 30)->nullable();
            $table->text('parent_address')->nullable();
            $table->text('parent_bio')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'father_name',
                'father_occupation',
                'father_phone',
                'mother_name',
                'mother_occupation',
                'mother_phone',
                'parent_address',
                'parent_bio',
            ]);
        });
    }
};
