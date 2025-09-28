<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientLoginController extends Controller
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

        $user = Auth::user();

        if ($user->is_admin) {
            Auth::logout();

            return back()
                ->withErrors(['email' => trans('auth.failed')])
                ->withInput($request->only('email'));
        }

        $status = strtolower((string) $user->verification_status);
        if ($status === 'active') {
            $user->forceFill(['verification_status' => 'approved'])->save();
            $status = 'approved';
        }

        $isVerified = $status === 'approved';

        if (! $isVerified) {
            return redirect()->route('user.verify');
        }

        return redirect()->intended(route('user.dashboard'));
    }
}
