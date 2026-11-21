<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGuruAccessIsLimited
{
    /**
     * Rute yang tetap boleh diakses guru selain bank soal dan laporan.
     * Profil, password, dan logout diperlukan untuk mengelola akun sendiri.
     *
     * @var list<string>
     */
    private const ALLOWED_ROUTE_NAMES = [
        'admin.profile.show',
        'admin.profile.update',
        'admin.password.edit',
        'admin.password.update',
        'admin.logout',
    ];

    /**
     * Seluruh sub-rute bank soal dan laporan boleh diakses guru.
     *
     * @var list<string>
     */
    private const ALLOWED_ROUTE_PREFIXES = [
        'admin.question-banks',
        'admin.reports.',
    ];

    /**
     * Batasi guru hanya pada menu yang diizinkan.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        $routeName = (string) $request->route()?->getName();

        // Admin sekolah dan kepala sekolah tetap memakai hak akses bawaan mereka,
        // meskipun field role mereka adalah "guru".
        if (! $user->isGuru() || $user->isSchoolAdmin() || $user->isHeadmaster()) {
            return $next($request);
        }

        if (in_array($routeName, self::ALLOWED_ROUTE_NAMES, true)) {
            return $next($request);
        }

        foreach (self::ALLOWED_ROUTE_PREFIXES as $prefix) {
            if (str_starts_with($routeName, $prefix)) {
                return $next($request);
            }
        }

        abort(403, 'Halaman ini hanya dapat diakses oleh administrator.');
    }
}
