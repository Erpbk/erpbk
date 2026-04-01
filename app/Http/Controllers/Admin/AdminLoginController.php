<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminLoginController extends Controller
{
    public function showLogin()
    {
        $pageConfigs = ['myLayout' => 'blank'];
        return view('admin.auth.login', compact('pageConfigs'));
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $identifier = $validated['email'];
        $password = $validated['password'];

        $guard = Auth::guard('admin');

        $loggedIn = $guard->attempt(['email' => $identifier, 'password' => $password, 'status' => 1]);
        if (!$loggedIn) {
            // Allow username login too.
            $loggedIn = $guard->attempt(['username' => $identifier, 'password' => $password, 'status' => 1]);
        }

        if (!$loggedIn) {
            return back()->withErrors([
                'email' => __('The provided credentials are incorrect.'),
            ])->withInput();
        }

        $request->session()->regenerate();

        // Do not use intended() here, because stale session intended URLs
        // (like main-domain /login) can hijack admin post-login redirect.
        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}

