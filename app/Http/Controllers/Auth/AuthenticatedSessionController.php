<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt(['email' => strtolower($credentials['email']), 'password' => $credentials['password']], $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records. Legacy wholesale accounts should use “Forgot password” before their first sign in.',
            ]);
        }

        $request->session()->regenerate();
        $user = $request->user();

        if ($user->account_type !== 'wholesale') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'This website is exclusively for approved wholesale accounts.',
            ]);
        }

        if (! $user->hasVerifiedEmail()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Please verify your email address before signing in.',
            ]);
        }

        if ($user->approval_status !== 'approved') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => $user->approval_status === 'pending'
                    ? 'Your wholesale account application is still awaiting approval.'
                    : 'This account is currently suspended. Please contact IGI Canada.',
            ]);
        }

        return redirect()->intended(route('account.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
