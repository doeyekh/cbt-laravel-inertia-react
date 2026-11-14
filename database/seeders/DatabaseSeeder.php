<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(SchoolSeeder::class);

        $primarySchool = School::query()->where('code', 'YAS-001')->first();
        $secondarySchool = School::query()->where('code', 'YAS-002')->first();

        // Super admin untuk yayasan (tidak terikat sekolah)
        User::factory()->administrator()->create([
            'name' => 'Super Admin',
            'username' => 'superadmin',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        // Admin sekolah contoh
        User::factory()->administrator()->create([
            'name' => 'Admin Sekolah',
            'username' => 'adminsekolah',
            'email' => 'adminsekolah@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
            'school_id' => $primarySchool?->id,
        ]);

        // Guru yang ditunjuk sebagai kepala sekolah dan admin sekolah
        $headmaster = User::factory()->guru()->create([
            'name' => 'Budi Santoso',
            'username' => 'guru1',
            'email' => 'guru1@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
            'school_id' => $primarySchool?->id,
        ]);

        $schoolAdmin = User::factory()->guru()->create([
            'name' => 'Siti Rahmawati',
            'username' => 'guru2',
            'email' => 'guru2@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
            'school_id' => $primarySchool?->id,
        ]);

        $secondaryHeadmaster = User::factory()->guru()->create([
            'name' => 'Andi Wijaya',
            'username' => 'guru3',
            'email' => 'guru3@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
            'school_id' => $secondarySchool?->id,
        ]);

        $primarySchool?->update([
            'headmaster_id' => $headmaster->id,
            'admin_id' => $schoolAdmin->id,
        ]);

        $secondarySchool?->update([
            'headmaster_id' => $secondaryHeadmaster->id,
            'admin_id' => null,
        ]);

        $this->call(AcademicYearSeeder::class);
        $this->call(ExamTypeSeeder::class);
        $this->call(SessionSeeder::class);
        $this->call(RoomSeeder::class);
        $this->call(SubjectSeeder::class);
        $this->call(StudentSeeder::class);
        $this->call(SubjectTeacherSeeder::class);
        $this->call(QuestionBankSeeder::class);
        $this->call(QuestionSeeder::class);
        $this->call(ExamPackageSeeder::class);
        $this->call(ExamParticipantSeeder::class);
    }
}
