<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsSchoolAdmin
{
    /**
     * Restrict the request to users assigned as a school administrator.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isSchoolAdmin() !== true) {
            abort(403, 'Halaman ini hanya dapat diakses oleh admin sekolah.');
        }

        return $next($request);
    }
}
