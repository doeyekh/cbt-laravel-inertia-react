<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Batas waktu pengerjaan disimpan di server agar durasi ujian tidak
     * bergantung pada jam di browser peserta. Baris lama dibiarkan null dan
     * dihitung dari waktu attempt dibuat (lihat ExamAttempt::resolveDeadline()).
     */
    public function up(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->timestamp('deadline_at')->nullable()->after('started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropColumn('deadline_at');
        });
    }
};
