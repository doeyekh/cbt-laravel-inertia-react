<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\Session;
use Illuminate\Database\Seeder;

class SessionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $schoolId = School::query()->orderBy('id')->value('id');

        if ($schoolId === null) {
            return;
        }

        $sessions = [
            [
                'code' => 'SESS01',
                'name' => 'Sesi Pagi',
                'start_time' => '07:00:00',
                'end_time' => '09:00:00',
                'is_active' => true,
            ],
            [
                'code' => 'SESS02',
                'name' => 'Sesi Siang',
                'start_time' => '09:30:00',
                'end_time' => '11:30:00',
                'is_active' => false,
            ],
            [
                'code' => 'SESS03',
                'name' => 'Sesi Sore',
                'start_time' => '13:00:00',
                'end_time' => '15:00:00',
                'is_active' => false,
            ],
            [
                'code' => 'SESS04',
                'name' => 'Sesi Malam',
                'start_time' => '15:30:00',
                'end_time' => '17:30:00',
                'is_active' => false,
            ],
        ];

        foreach ($sessions as $session) {
            Session::firstOrNew([
                'school_id' => $schoolId,
                'code' => $session['code'],
            ])
                ->forceFill([...$session, 'school_id' => $schoolId])
                ->save();
        }
    }
}
