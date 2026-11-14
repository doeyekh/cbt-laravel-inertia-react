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
        $tables = [
            'exam_types' => 'exam_types_code_unique',
            'exam_sessions' => 'exam_sessions_code_unique',
            'rooms' => 'rooms_code_unique',
            'assessment_types' => 'assessment_types_code_unique',
        ];

        $defaultSchoolId = DB::table('schools')->orderBy('id')->value('id');

        foreach ($tables as $tableName => $uniqueIndex) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreignId('school_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('schools')
                    ->nullOnDelete();
            });

            if ($defaultSchoolId !== null) {
                DB::table($tableName)
                    ->whereNull('school_id')
                    ->update(['school_id' => $defaultSchoolId]);
            }

            Schema::table($tableName, function (Blueprint $table) use ($uniqueIndex): void {
                $table->dropUnique($uniqueIndex);
                $table->unique(['school_id', 'code']);
            });
        }

        Schema::table('assessment_types', function (Blueprint $table): void {
            $table->decimal('weight', 5, 2)->default(0)->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assessment_types', function (Blueprint $table): void {
            $table->dropColumn('weight');
        });

        $tables = [
            'exam_types',
            'exam_sessions',
            'rooms',
            'assessment_types',
        ];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropUnique(['school_id', 'code']);
            });

            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropConstrainedForeignId('school_id');
            });

            Schema::table($tableName, function (Blueprint $table): void {
                $table->unique('code');
            });
        }
    }
};
