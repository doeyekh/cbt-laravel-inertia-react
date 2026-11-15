<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentIsAuthenticated
{
    /**
     * Redirect guests of the student portal back to the student login page.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('student_participant_id')) {
            return redirect()
                ->route('student.login')
                ->with('toast', [
                    'type' => 'info',
                    'title' => 'Sesi Diperlukan',
                    'description' => 'Silakan masuk untuk membuka portal siswa.',
                ]);
        }

        return $next($request);
    }
}
