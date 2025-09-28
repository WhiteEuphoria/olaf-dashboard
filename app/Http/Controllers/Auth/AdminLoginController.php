<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminLoginController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => trans('auth.failed')])
                ->withInput($request->only('email'));
        }

        $request->session()->regenerate();

        if (! Auth::user()->is_admin) {
            Auth::logout();

            return back()
                ->withErrors(['email' => trans('auth.failed')])
                ->withInput($request->only('email'));
        }

        return redirect()->intended(route('admin.dashboard'));
    }
}

