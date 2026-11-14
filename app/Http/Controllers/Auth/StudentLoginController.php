<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ExamParticipant;
use App\Models\School;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class StudentLoginController extends Controller
{
    /**
     * Display the student login view.
     */
    public function create(): Response
    {
        $activeAcademicYear = AcademicYear::where('is_active', true)->first();

        return Inertia::render('Auth/StudentLogin', [
            'title' => 'Portal Masuk Siswa — CBT Online',
            'systemInfo' => [
                'appName' => 'EduTest CBT',
                'systemName' => 'Sistem Evaluasi Terpadu',
                'academicYear' => $activeAcademicYear ? "{$activeAcademicYear->name} - {$activeAcademicYear->semester}" : null,
                'clusterNode' => 'Cluster CBT-Node 01',
                'serverStatus' => 'Online / Normal',
                'serverLatency' => '12ms',
                'version' => 'v4.6.2-Rel',
            ],
        ]);
    }

    /**
     * Handle an incoming student authentication request.
     */
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'max:100'],
            'token' => ['nullable', 'string', 'max:20'],
        ], [
            'username.required' => 'Nomor peserta atau username wajib diisi.',
            'password.required' => 'Kata sandi atau PIN ujian wajib diisi.',
        ]);

        $username = trim($credentials['username']);
        $password = $credentials['password'];

        // Cari peserta ujian berdasarkan username atau nomor peserta
        $participant = ExamParticipant::query()
            ->with(['student', 'school', 'academicYear', 'session', 'room'])
            ->where(function ($query) use ($username): void {
                $query->where('username', $username)
                    ->orWhere('participant_number', $username);
            })
            ->first();

        if (! $participant) {
            return back()->withErrors([
                'username' => 'Nomor peserta / username tidak ditemukan. Pastikan data sesuai dengan kartu ujian.',
            ])->withInput($request->only('username', 'token'));
        }

        // Verifikasi password peserta (bisa hash atau plain match sesuai implementasi)
        $passwordMatches = false;
        if (! empty($participant->password) && Hash::check($password, $participant->password)) {
            $passwordMatches = true;
        } elseif ($participant->password_plain === $password) {
            $passwordMatches = true;
        }

        if (! $passwordMatches) {
            return back()->withErrors([
                'password' => 'Kata sandi atau PIN ujian salah. Periksa kembali kartu peserta Anda.',
            ])->withInput($request->only('username', 'token'));
        }

        // Simpan sesi login siswa
        $request->session()->put('student_participant_id', $participant->id);
        $request->session()->put('student_name', $participant->student?->name ?? 'Siswa');

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Login Berhasil',
            'description' => 'Selamat datang, '.($participant->student?->name ?? 'Peserta Ujian').'. Sesi ujian siap dimulai.',
        ]);
    }

    /**
     * Destroy student session (logout).
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->forget(['student_participant_id', 'student_name']);

        return redirect()->route('student.login')->with('toast', [
            'type' => 'info',
            'title' => 'Sesi Diakhiri',
            'description' => 'Anda telah keluar dari portal ujian.',
        ]);
    }
}
