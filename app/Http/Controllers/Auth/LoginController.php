<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'title' => 'Masuk ke CBT Panel',
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $loginField = filter_var($request->input('username'), FILTER_VALIDATE_EMAIL)
            ? 'email'
            : 'username';

        $throttleKey = 'login:'.Str::lower($request->input('username')).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors([
                'username' => 'Terlalu banyak percobaan login. Coba lagi dalam '.$seconds.' detik.',
            ])->withInput();
        }

        if (! Auth::attempt([
            $loginField => $request->input('username'),
            'password' => $request->input('password'),
            'is_active' => true,
        ], $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey);

            return back()->withErrors([
                'username' => 'Kredensial yang Anda masukkan tidak sesuai dengan data kami.',
            ])->withInput();
        }

        RateLimiter::clear($throttleKey);

        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }
}
