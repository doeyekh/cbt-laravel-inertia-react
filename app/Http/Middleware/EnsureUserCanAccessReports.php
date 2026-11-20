<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserCanAccessReports
{
    /**
     * Laporan dapat diakses admin sekolah dan guru.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(403, 'Halaman ini hanya dapat diakses oleh admin sekolah atau guru.');
        }

        if ($user->isAdministrator() && ! $user->isSchoolAdmin()) {
            abort(403, 'Halaman ini hanya dapat diakses oleh admin sekolah atau guru.');
        }

        if (! $user->isSchoolAdmin() && ! $user->isGuru()) {
            abort(403, 'Halaman ini hanya dapat diakses oleh admin sekolah atau guru.');
        }

        return $next($request);
    }
}
