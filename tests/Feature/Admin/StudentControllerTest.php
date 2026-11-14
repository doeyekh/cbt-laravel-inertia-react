<?php

namespace Tests\Feature\Admin;

use App\Enums\Gender;
use App\Enums\RegistrationStatus;
use App\Models\AcademicYear;
use App\Models\ClassMember;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StudentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->administrator()->create());
    }

    public function test_index_lists_registered_students_with_current_class(): void
    {
        $academicYear = AcademicYear::factory()->create(['is_active' => true]);
        $school = School::factory()->create();
        $schoolClass = SchoolClass::factory()->create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
        ]);

        $student = Student::factory()->create(['name' => 'Budi Santoso']);
        StudentRegistration::factory()
            ->forStudent($student)
            ->forSchool($school, $academicYear)
            ->create(['status' => RegistrationStatus::Aktif]);
        ClassMember::factory()->forClass($schoolClass, $student)->create();

        $this->get(route('admin.students.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Students/Index')
                ->has('students.data', 1)
                ->where('students.data.0.name', 'Budi Santoso')
                ->where('students.data.0.registration.school_name', $school->name)
                ->where('students.data.0.current_class.name', $schoolClass->name)
                ->where('students.data.0.is_active', true)
                ->where('stats.total', 1)
                ->where('stats.active', 1)
                ->where('stats.inactive', 0)
                ->has('schools')
                ->has('classes')
                ->has('academicYears')
            );
    }

    public function test_active_scope_only_lists_students_with_an_active_registration(): void
    {
        $academicYear = AcademicYear::factory()->create(['is_active' => true]);
        $school = School::factory()->create();

        $activeStudent = Student::factory()->create(['name' => 'Siswa Aktif']);
        StudentRegistration::factory()
            ->forStudent($activeStudent)
            ->forSchool($school, $academicYear)
            ->create(['status' => RegistrationStatus::Aktif]);

        $alumniStudent = Student::factory()->create(['name' => 'Siswa Lulus']);
        StudentRegistration::factory()
            ->forStudent($alumniStudent)
            ->forSchool($school, $academicYear)
            ->create(['status' => RegistrationStatus::Lulus]);

        $this->get(route('admin.students.active'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Students/Index')
                ->where('scope', 'active')
                ->has('students.data', 1)
                ->where('students.data.0.name', 'Siswa Aktif')
                ->where('stats.total', 2)
                ->where('stats.active', 1)
                ->where('stats.inactive', 1)
            );
    }

    public function test_alumni_scope_only_lists_students_without_an_active_registration(): void
    {
        $academicYear = AcademicYear::factory()->create(['is_active' => true]);
        $school = School::factory()->create();

        $activeStudent = Student::factory()->create(['name' => 'Siswa Aktif']);
        StudentRegistration::factory()
            ->forStudent($activeStudent)
            ->forSchool($school, $academicYear)
            ->create(['status' => RegistrationStatus::Aktif]);

        $alumniStudent = Student::factory()->create(['name' => 'Siswa Lulus']);
        StudentRegistration::factory()
            ->forStudent($alumniStudent)
            ->forSchool($school, $academicYear)
            ->create(['status' => RegistrationStatus::Lulus]);

        $this->get(route('admin.students.alumni'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Students/Index')
                ->where('scope', 'alumni')
                ->has('students.data', 1)
                ->where('students.data.0.name', 'Siswa Lulus')
                ->where('filters.status', null)
                ->where('stats.total', 2)
                ->where('stats.active', 1)
                ->where('stats.inactive', 1)
            );
    }

    public function test_index_is_scoped_to_the_schools_a_school_admin_can_access(): void
    {
        $academicYear = AcademicYear::factory()->create(['is_active' => true]);
        $schoolAdmin = User::factory()->guru()->create();
        $mySchool = School::factory()->create(['admin_id' => $schoolAdmin->id]);
        $otherSchool = School::factory()->create();

        $myStudent = Student::factory()->create(['name' => 'Siswa Sekolah Sendiri']);
        StudentRegistration::factory()->forStudent($myStudent)->forSchool($mySchool, $academicYear)->create();

        $otherStudent = Student::factory()->create(['name' => 'Siswa Sekolah Lain']);
        StudentRegistration::factory()->forStudent($otherStudent)->forSchool($otherSchool, $academicYear)->create();

        $this->actingAs($schoolAdmin)
            ->get(route('admin.students.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('students.data', 1)
                ->where('students.data.0.name', 'Siswa Sekolah Sendiri')
            );
    }

    public function test_guru_cannot_access_the_student_module(): void
    {
        $this->actingAs(User::factory()->guru()->create())
            ->get(route('admin.students.index'))
            ->assertForbidden();
    }

    public function test_headmaster_can_view_students_but_cannot_manage_them(): void
    {
        $headmaster = User::factory()->guru()->create();
        $school = School::factory()->create(['headmaster_id' => $headmaster->id]);
        $academicYear = AcademicYear::factory()->create(['is_active' => true]);

        $student = Student::factory()->create();
        StudentRegistration::factory()->forStudent($student)->forSchool($school, $academicYear)->create();

        $this->actingAs($headmaster)
            ->get(route('admin.students.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('students.data', 1));

        $this->actingAs($headmaster)
            ->post(route('admin.students.store'), [
                'school_id' => $school->id,
                'name' => 'Siswa Baru',
                'gender' => Gender::LakiLaki->value,
                'nis' => 'NIS-001',
                'entry_academic_year_id' => $academicYear->id,
                'status' => RegistrationStatus::Aktif->value,
            ])
            ->assertForbidden();
    }

    public function test_store_creates_a_student_with_a_registration_and_photo(): void
    {
        Storage::fake('public');

        $school = School::factory()->create();
        $academicYear = AcademicYear::factory()->create(['is_active' => true]);

        $response = $this->post(route('admin.students.store'), [
            'school_id' => $school->id,
            'nisn' => '0099887766',
            'name' => 'Budi Santoso',
            'gender' => Gender::LakiLaki->value,
            'birth_place' => 'Bandung',
            'birth_date' => '2010-05-12',
            'photo' => UploadedFile::fake()->image('budi.jpg', 120, 120),
            'father_name' => 'Ahmad Santoso',
            'father_occupation' => 'Karyawan Swasta',
            'father_phone' => '081234567890',
            'mother_name' => 'Siti Rahayu',
            'mother_occupation' => 'Guru',
            'mother_phone' => '081298765432',
            'parent_address' => 'Jl. Merdeka No. 10, Bandung',
            'parent_bio' => 'Orang tua tinggal bersama siswa.',
            'nis' => 'NIS-001',
            'entry_academic_year_id' => $academicYear->id,
            'status' => RegistrationStatus::Aktif->value,
        ]);

        $response->assertRedirect()->assertSessionHas('toast');

        $student = Student::query()->where('nisn', '0099887766')->firstOrFail();

        $this->assertSame('Budi Santoso', $student->name);
        $this->assertSame(Gender::LakiLaki, $student->gender);
        $this->assertSame('Ahmad Santoso', $student->father_name);
        $this->assertSame('Karyawan Swasta', $student->father_occupation);
        $this->assertSame('081234567890', $student->father_phone);
        $this->assertSame('Siti Rahayu', $student->mother_name);
        $this->assertSame('Guru', $student->mother_occupation);
        $this->assertSame('081298765432', $student->mother_phone);
        $this->assertSame('Jl. Merdeka No. 10, Bandung', $student->parent_address);
        $this->assertSame('Orang tua tinggal bersama siswa.', $student->parent_bio);
        $this->assertNotNull($student->photo_path);
        Storage::disk('public')->assertExists($student->photo_path);

        $this->assertDatabaseHas('student_registrations', [
            'student_id' => $student->id,
            'school_id' => $school->id,
            'nis' => 'NIS-001',
            'entry_academic_year_id' => $academicYear->id,
            'status' => RegistrationStatus::Aktif->value,
        ]);
    }

    public function test_store_uses_the_active_academic_year_instead_of_the_submitted_year(): void
    {
        $activeAcademicYear = AcademicYear::factory()->create(['is_active' => true]);
        $inactiveAcademicYear = AcademicYear::factory()->create(['is_active' => false]);
        $school = School::factory()->create();

        $this->post(route('admin.students.store'), [
            'school_id' => $school->id,
            'name' => 'Siswa Baru',
            'gender' => Gender::LakiLaki->value,
            'nis' => 'NIS-001',
            'entry_academic_year_id' => $inactiveAcademicYear->id,
            'status' => RegistrationStatus::Aktif->value,
        ])->assertRedirect()->assertSessionHas('toast');

        $student = Student::query()->where('name', 'Siswa Baru')->firstOrFail();

        $this->assertDatabaseHas('student_registrations', [
            'student_id' => $student->id,
            'entry_academic_year_id' => $activeAcademicYear->id,
        ]);
    }

    public function test_store_requires_exit_year_when_registration_status_is_not_active(): void
    {
        AcademicYear::factory()->create(['is_active' => true]);
        $school = School::factory()->create();

        $this->post(route('admin.students.store'), [
            'school_id' => $school->id,
            'name' => 'Siswa Lulus',
            'gender' => Gender::LakiLaki->value,
            'nis' => 'NIS-001',
            'status' => RegistrationStatus::Lulus->value,
        ])->assertSessionHasErrors('exit_academic_year_id');

        $this->assertDatabaseCount('students', 0);
    }

    public function test_store_saves_exit_year_when_registration_status_is_not_active(): void
    {
        AcademicYear::factory()->create(['is_active' => true]);
        $exitAcademicYear = AcademicYear::factory()->create(['is_active' => false]);
        $school = School::factory()->create();

        $this->post(route('admin.students.store'), [
            'school_id' => $school->id,
            'name' => 'Siswa Lulus',
            'gender' => Gender::LakiLaki->value,
            'nis' => 'NIS-001',
            'status' => RegistrationStatus::Lulus->value,
            'exit_academic_year_id' => $exitAcademicYear->id,
        ])->assertRedirect()->assertSessionHas('toast');

        $student = Student::query()->where('name', 'Siswa Lulus')->firstOrFail();

        $this->assertDatabaseHas('student_registrations', [
            'student_id' => $student->id,
            'status' => RegistrationStatus::Lulus->value,
            'exit_academic_year_id' => $exitAcademicYear->id,
        ]);
    }

    public function test_store_requires_an_active_academic_year(): void
    {
        AcademicYear::factory()->create(['is_active' => false]);
        $school = School::factory()->create();

        $this->post(route('admin.students.store'), [
            'school_id' => $school->id,
            'name' => 'Siswa Baru',
            'gender' => Gender::LakiLaki->value,
            'nis' => 'NIS-001',
            'status' => RegistrationStatus::Aktif->value,
        ])->assertRedirect()->assertSessionHasErrors('school_id');

        $this->assertDatabaseCount('students', 0);
    }

    public function test_store_rejects_a_duplicate_nisn(): void
    {
        $school = School::factory()->create();
        $academicYear = AcademicYear::factory()->create(['is_active' => true]);
        Student::factory()->create(['nisn' => '0011223344']);

        $this->post(route('admin.students.store'), [
            'school_id' => $school->id,
            'nisn' => '0011223344',
            'name' => 'Siswa Lain',
            'gender' => Gender::Perempuan->value,
            'nis' => 'NIS-002',
            'entry_academic_year_id' => $academicYear->id,
            'status' => RegistrationStatus::Aktif->value,
        ])->assertSessionHasErrors('nisn');

        $this->assertDatabaseCount('students', 1);
    }

    public function test_store_rejects_a_duplicate_nis_in_the_same_school(): void
    {
        $school = School::factory()->create();
        $academicYear = AcademicYear::factory()->create(['is_active' => true]);

        $existing = StudentRegistration::factory()
            ->forSchool($school, $academicYear)
            ->create(['nis' => 'NIS-001']);

        $this->assertNotNull($existing);

        $this->post(route('admin.students.store'), [
            'school_id' => $school->id,
            'name' => 'Siswa Baru',
            'gender' => Gender::Perempuan->value,
            'nis' => 'NIS-001',
            'entry_academic_year_id' => $academicYear->id,
            'status' => RegistrationStatus::Aktif->value,
        ])->assertSessionHasErrors('nis');

        $this->assertDatabaseCount('students', 1);
    }

    public function test_store_rejects_a_school_the_school_admin_cannot_access(): void
    {
        $schoolAdmin = User::factory()->guru()->create();
        School::factory()->create(['admin_id' => $schoolAdmin->id]);
        $otherSchool = School::factory()->create();
        $academicYear = AcademicYear::factory()->create(['is_active' => true]);

        $this->actingAs($schoolAdmin)
            ->post(route('admin.students.store'), [
                'school_id' => $otherSchool->id,
                'name' => 'Siswa Baru',
                'gender' => Gender::LakiLaki->value,
                'nis' => 'NIS-001',
                'entry_academic_year_id' => $academicYear->id,
                'status' => RegistrationStatus::Aktif->value,
            ])
            ->assertSessionHasErrors('school_id');

        $this->assertDatabaseCount('students', 0);
    }

    public function test_update_changes_the_student_identity(): void
    {
        $student = Student::factory()->create([
            'name' => 'Nama Lama',
            'gender' => Gender::LakiLaki,
        ]);

        $this->put(route('admin.students.update', $student), [
            'nisn' => '7777888899',
            'name' => 'Nama Baru',
            'gender' => Gender::Perempuan->value,
            'birth_place' => 'Jakarta',
            'birth_date' => '2009-03-01',
            'father_name' => 'Ayah Baru',
            'mother_name' => 'Ibu Baru',
            'parent_address' => 'Jl. Baru No. 1',
            'parent_bio' => 'Catatan baru orang tua.',
        ])->assertRedirect()->assertSessionHas('toast');

        $student->refresh();

        $this->assertSame('Nama Baru', $student->name);
        $this->assertSame('7777888899', $student->nisn);
        $this->assertSame(Gender::Perempuan, $student->gender);
        $this->assertSame('Ayah Baru', $student->father_name);
        $this->assertSame('Ibu Baru', $student->mother_name);
        $this->assertSame('Jl. Baru No. 1', $student->parent_address);
        $this->assertSame('Catatan baru orang tua.', $student->parent_bio);
    }

    public function test_destroy_is_blocked_while_the_student_has_an_active_registration(): void
    {
        $student = Student::factory()->create();
        StudentRegistration::factory()
            ->forStudent($student)
            ->create(['status' => RegistrationStatus::Aktif]);

        $this->delete(route('admin.students.destroy', $student))
            ->assertRedirect()
            ->assertSessionHas('toast');

        $this->assertNotSoftDeleted($student);
    }

    public function test_destroy_soft_deletes_a_student_without_active_registration_or_class_member(): void
    {
        $student = Student::factory()->create();
        StudentRegistration::factory()
            ->forStudent($student)
            ->create(['status' => RegistrationStatus::Pindah]);

        $this->delete(route('admin.students.destroy', $student))
            ->assertRedirect()
            ->assertSessionHas('toast');

        $this->assertSoftDeleted($student);
    }

    public function test_import_creates_students_and_skips_invalid_rows(): void
    {
        $school = School::factory()->create();
        $academicYear = AcademicYear::factory()->create(['is_active' => true]);
        AcademicYear::factory()->create(['is_active' => false]);

        $csv = implode("\n", [
            'nis,nama,jenis_kelamin,nisn,nama_ayah,pekerjaan_ayah,no_hp_ayah,nama_ibu,pekerjaan_ibu,no_hp_ibu,alamat_orang_tua,biografi_orang_tua',
            'NIS-001,Budi Santoso,L,1111111111,Ahmad,Karyawan,081234567890,Siti,Guru,081298765432,Jl. Merdeka No. 10,Orang tua tinggal bersama siswa',
            'NIS-002,Siti Aminah,P,1111111111,,,,,,,,',
        ]);

        $this->post(route('admin.students.import'), [
            'file' => UploadedFile::fake()->createWithContent('students.csv', $csv),
            'school_id' => $school->id,
        ])->assertRedirect()->assertSessionHas('toast');

        $this->assertDatabaseCount('students', 1);
        $this->assertDatabaseCount('student_registrations', 1);
        $this->assertDatabaseHas('students', [
            'name' => 'Budi Santoso',
            'nisn' => '1111111111',
            'father_name' => 'Ahmad',
            'father_occupation' => 'Karyawan',
            'father_phone' => '081234567890',
            'mother_name' => 'Siti',
            'mother_occupation' => 'Guru',
            'mother_phone' => '081298765432',
            'parent_address' => 'Jl. Merdeka No. 10',
            'parent_bio' => 'Orang tua tinggal bersama siswa',
        ]);
        $this->assertDatabaseHas('student_registrations', [
            'school_id' => $school->id,
            'nis' => 'NIS-001',
            'entry_academic_year_id' => $academicYear->id,
            'status' => RegistrationStatus::Aktif->value,
        ]);
    }

    public function test_import_requires_an_active_academic_year(): void
    {
        AcademicYear::factory()->create(['is_active' => false]);
        $school = School::factory()->create();

        $csv = "nis,nama,jenis_kelamin\nNIS-001,Budi Santoso,L";

        $this->post(route('admin.students.import'), [
            'file' => UploadedFile::fake()->createWithContent('students.csv', $csv),
            'school_id' => $school->id,
        ])->assertRedirect()->assertSessionHas('toast');

        $this->assertDatabaseCount('students', 0);
    }

    public function test_import_rejects_an_inaccessible_school(): void
    {
        $schoolAdmin = User::factory()->guru()->create();
        School::factory()->create(['admin_id' => $schoolAdmin->id]);
        $otherSchool = School::factory()->create();
        $academicYear = AcademicYear::factory()->create(['is_active' => true]);

        $csv = "nis,nama,jenis_kelamin\nNIS-001,Budi Santoso,L";

        $this->actingAs($schoolAdmin)
            ->post(route('admin.students.import'), [
                'file' => UploadedFile::fake()->createWithContent('students.csv', $csv),
                'school_id' => $otherSchool->id,
                'entry_academic_year_id' => $academicYear->id,
            ])
            ->assertSessionHasErrors('school_id');

        $this->assertDatabaseCount('students', 0);
    }

    public function test_bulk_graduate_marks_selected_active_registrations_as_graduated(): void
    {
        $academicYear = AcademicYear::factory()->create(['is_active' => true]);
        $school = School::factory()->create();

        $firstStudent = Student::factory()->create();
        $firstRegistration = StudentRegistration::factory()
            ->forStudent($firstStudent)
            ->forSchool($school, $academicYear)
            ->create(['status' => RegistrationStatus::Aktif]);

        $secondStudent = Student::factory()->create();
        $secondRegistration = StudentRegistration::factory()
            ->forStudent($secondStudent)
            ->forSchool($school, $academicYear)
            ->create(['status' => RegistrationStatus::Aktif]);

        $alreadyGraduated = Student::factory()->create();
        $graduatedRegistration = StudentRegistration::factory()
            ->forStudent($alreadyGraduated)
            ->forSchool($school, $academicYear)
            ->create(['status' => RegistrationStatus::Lulus]);

        $this->patch(route('admin.students.bulk-graduate'), [
            'student_ids' => [$firstStudent->id, $secondStudent->id, $alreadyGraduated->id],
        ])->assertRedirect()->assertSessionHas('toast');

        $this->assertSame(RegistrationStatus::Lulus, $firstRegistration->fresh()->status);
        $this->assertSame(RegistrationStatus::Lulus, $secondRegistration->fresh()->status);
        $this->assertSame(RegistrationStatus::Lulus, $graduatedRegistration->fresh()->status);
        $this->assertSame($academicYear->id, $firstRegistration->fresh()->exit_academic_year_id);
        $this->assertSame($academicYear->id, $secondRegistration->fresh()->exit_academic_year_id);
    }

    public function test_bulk_graduate_is_limited_to_school_admin_accessible_schools(): void
    {
        $schoolAdmin = User::factory()->guru()->create();
        $ownSchool = School::factory()->create(['admin_id' => $schoolAdmin->id]);
        $otherSchool = School::factory()->create();
        $academicYear = AcademicYear::factory()->create(['is_active' => true]);

        $ownStudent = Student::factory()->create();
        $ownRegistration = StudentRegistration::factory()
            ->forStudent($ownStudent)
            ->forSchool($ownSchool, $academicYear)
            ->create(['status' => RegistrationStatus::Aktif]);

        $otherStudent = Student::factory()->create();
        $otherRegistration = StudentRegistration::factory()
            ->forStudent($otherStudent)
            ->forSchool($otherSchool, $academicYear)
            ->create(['status' => RegistrationStatus::Aktif]);

        $this->actingAs($schoolAdmin)
            ->patch(route('admin.students.bulk-graduate'), [
                'student_ids' => [$ownStudent->id, $otherStudent->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('toast');

        $this->assertSame(RegistrationStatus::Lulus, $ownRegistration->fresh()->status);
        $this->assertSame(RegistrationStatus::Aktif, $otherRegistration->fresh()->status);
    }

    public function test_headmaster_cannot_bulk_graduate_students(): void
    {
        $headmaster = User::factory()->guru()->create();
        $school = School::factory()->create(['headmaster_id' => $headmaster->id]);
        $academicYear = AcademicYear::factory()->create(['is_active' => true]);

        $student = Student::factory()->create();
        StudentRegistration::factory()
            ->forStudent($student)
            ->forSchool($school, $academicYear)
            ->create(['status' => RegistrationStatus::Aktif]);

        $this->actingAs($headmaster)
            ->patch(route('admin.students.bulk-graduate'), [
                'student_ids' => [$student->id],
            ])
            ->assertForbidden();
    }
}
