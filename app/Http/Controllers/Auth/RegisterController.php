<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function show(): View
    {
        return view('auth.register');
    }

    public function store(
        RegisterRequest $request
    ): RedirectResponse {
        $data = $request->safe()->only([
            'name',
            'email',
            'password',
        ]);

        $user = User::create($data);

        event(new Registered($user));

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()
            ->route('repair-requests.index')
            ->with('success', 'Реєстрацію успішно завершено.');
    }
}
