<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\SchoolClass;
use App\Services\ExamParticipantCredentialService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExamParticipantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $school = School::query()->whereNull('deleted_at')->orderBy('id')->first();
        $academicYear = AcademicYear::active();

        if ($school === null || $academicYear === null) {
            return;
        }

        $schoolClasses = SchoolClass::query()
            ->where('school_id', $school->id)
            ->where('academic_year_id', $academicYear->id)
            ->whereHas('classMembers')
            ->orderBy('code')
            ->limit(2)
            ->get();

        if ($schoolClasses->isEmpty()) {
            return;
        }

        $service = app(ExamParticipantCredentialService::class);

        DB::transaction(function () use ($service, $school, $academicYear, $schoolClasses): void {
            $placement = $service->currentPlacement((int) $school->id, (int) $academicYear->id);

            $service->syncParticipantsFromClasses(
                $school,
                $academicYear,
                $schoolClasses->pluck('id')->all(),
                $placement,
            );

            $participants = $service->participantsWithRelations($school, $academicYear);

            if ($participants->isNotEmpty()) {
                $service->generateCredentials($school, $academicYear, $participants);
            }
        });
    }
}
