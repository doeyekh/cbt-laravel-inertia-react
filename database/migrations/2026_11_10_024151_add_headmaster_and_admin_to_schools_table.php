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
        Schema::table('schools', function (Blueprint $table) {
            $table->foreignId('headmaster_id')
                ->nullable()
                ->after('address')
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('admin_id')
                ->nullable()
                ->after('headmaster_id')
                ->constrained('users')
                ->nullOnDelete();
        });

        if (Schema::hasColumn('schools', 'principal')) {
            Schema::table('schools', function (Blueprint $table) {
                $table->dropColumn('principal');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropConstrainedForeignId('admin_id');
            $table->dropConstrainedForeignId('headmaster_id');
            $table->string('principal')->nullable()->after('address');
        });
    }
};
