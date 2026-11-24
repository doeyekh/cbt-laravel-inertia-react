<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserCanAccessLeger
{
    /**
     * Leger Nilai Hanya Bisa diakses oleh Administrator dan Admin Sekolah
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user === null || (! $user->isAdministrator() && ! $user->isSchoolAdmin())) {
            abort(403, 'Halaman Ini Hanya dapat Diakses Oleh Administrator');
        }

        return $next($request);
    }
}
