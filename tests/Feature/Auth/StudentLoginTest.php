<?php

namespace Tests\Feature\Auth;

use App\Models\AcademicYear;
use App\Models\ExamParticipant;
use App\Models\School;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StudentLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Auth/StudentLogin')
            ->has('title')
            ->has('systemInfo')
        );
    }

    public function test_student_login_screen_can_be_rendered_via_login_route(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Auth/StudentLogin')
        );
    }

    public function test_student_login_screen_renders_active_academic_year_label(): void
    {
        AcademicYear::factory()
            ->ganjil()
            ->active()
            ->create(['name' => '2026/2027']);

        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Auth/StudentLogin')
            ->where('systemInfo.academicYear', '2026/2027 - Ganjil')
        );
    }

    public function test_student_cannot_login_without_required_fields(): void
    {
        $response = $this->from('/login')->post('/login', [
            'username' => '',
            'password' => '',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['username', 'password']);
    }

    public function test_student_cannot_login_with_invalid_username(): void
    {
        $response = $this->from('/login')->post('/login', [
            'username' => 'nonexistent_user',
            'password' => 'secret123',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['username']);
    }

    public function test_student_cannot_login_with_participant_number(): void
    {
        $school = School::factory()->create();
        $academicYear = AcademicYear::factory()->create(['is_active' => true]);
        $student = Student::factory()->create();

        ExamParticipant::create([
            'student_id' => $student->id,
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'username' => 'SISWA001',
            'participant_number' => '01-001',
            'password' => Hash::make('rahasia123'),
            'password_plain' => 'rahasia123',
        ]);

        $response = $this->from('/login')->post('/login', [
            'username' => '01-001',
            'password' => 'rahasia123',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['username']);
    }

    public function test_student_cannot_login_with_incorrect_password(): void
    {
        $school = School::factory()->create();
        $academicYear = AcademicYear::factory()->create(['is_active' => true]);
        $student = Student::factory()->create();

        ExamParticipant::create([
            'student_id' => $student->id,
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'username' => 'SISWA001',
            'participant_number' => '01-001',
            'password' => Hash::make('rahasia123'),
            'password_plain' => 'rahasia123',
        ]);

        $response = $this->from('/login')->post('/login', [
            'username' => 'SISWA001',
            'password' => 'wrongpassword',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['password']);
    }

    public function test_student_can_login_with_valid_username_and_password(): void
    {
        $school = School::factory()->create();
        $academicYear = AcademicYear::factory()->create(['is_active' => true]);
        $student = Student::factory()->create(['name' => 'Budi Pratama']);

        $participant = ExamParticipant::create([
            'student_id' => $student->id,
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'username' => 'BUDI001',
            'participant_number' => '01-001-05',
            'password' => Hash::make('pin12345'),
            'password_plain' => 'pin12345',
        ]);

        $response = $this->from('/login')->post('/login', [
            'username' => 'BUDI001',
            'password' => 'pin12345',
        ]);

        $response->assertRedirect(route('student.dashboard'));
        $response->assertSessionHas('student_participant_id', $participant->id);
        $response->assertSessionHas('student_name', 'Budi Pratama');
        $response->assertSessionHas('toast.type', 'success');
    }

    public function test_student_can_logout(): void
    {
        $response = $this->withSession([
            'student_participant_id' => 123,
            'student_name' => 'Budi Pratama',
        ])->post('/logout');

        $response->assertRedirect('/');
        $response->assertSessionMissing('student_participant_id');
        $response->assertSessionMissing('student_name');
    }
}
