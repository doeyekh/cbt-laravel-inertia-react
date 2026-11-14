<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdministrator
{
    /**
     * Restrict the request to users with the administrator role.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->role !== UserRole::Administrator) {
            abort(403, 'Halaman ini hanya dapat diakses oleh administrator.');
        }

        return $next($request);
    }
}
