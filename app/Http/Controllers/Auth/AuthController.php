<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function show(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();

        $key = 'login:' . hash(
            'sha256',
            strtolower(trim($credentials['email'])) . '|' . $request->ip()
        );

        if (RateLimiter::tooManyAttempts($key, 5)) {
            abort(429, 'Забагато спроб входу');
        }

        if (!Auth::attempt($credentials)) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages(['email' => 'Неправильна пошта чи пароль']);
        }

        RateLimiter::clear($key);

        $request->session()->regenerate();

        return redirect()->intended(route('repair-requests.index'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
