<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\School;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
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

        $rooms = [
            [
                'code' => 'RM01',
                'name' => 'Ruangan Ujian 1',
                'building' => 'Gedung A',
                'floor' => '1',
                'capacity' => 40,
                'is_active' => true,
            ],
            [
                'code' => 'RM02',
                'name' => 'Ruangan Ujian 2',
                'building' => 'Gedung A',
                'floor' => '1',
                'capacity' => 40,
                'is_active' => false,
            ],
            [
                'code' => 'RM03',
                'name' => 'Ruangan Ujian 3',
                'building' => 'Gedung B',
                'floor' => '2',
                'capacity' => 35,
                'is_active' => false,
            ],
            [
                'code' => 'RM04',
                'name' => 'Ruangan Ujian 4',
                'building' => 'Gedung B',
                'floor' => '2',
                'capacity' => 35,
                'is_active' => false,
            ],
            [
                'code' => 'RM05',
                'name' => 'Ruangan Ujian 5',
                'building' => 'Gedung C',
                'floor' => '3',
                'capacity' => 50,
                'is_active' => false,
            ],
        ];

        foreach ($rooms as $room) {
            Room::firstOrNew([
                'school_id' => $schoolId,
                'code' => $room['code'],
            ])
                ->forceFill([...$room, 'school_id' => $schoolId])
                ->save();
        }
    }
}
