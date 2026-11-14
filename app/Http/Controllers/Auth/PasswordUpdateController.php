<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class PasswordUpdateController extends Controller
{
    /**
     * Show the password update page.
     */
    public function edit(): Response
    {
        return Inertia::render('Auth/UpdatePassword', [
            'title' => 'Ganti Kata Sandi',
        ]);
    }

    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($request->input('current_password'), $user->password)) {
            return back()->withErrors([
                'current_password' => 'Kata sandi saat ini tidak sesuai.',
            ])->withInput();
        }

        $user->update([
            'password' => Hash::make($request->input('password')),
        ]);

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Kata Sandi Diperbarui',
            'description' => 'Kata sandi Anda telah berhasil diperbarui.',
        ]);
    }
}
