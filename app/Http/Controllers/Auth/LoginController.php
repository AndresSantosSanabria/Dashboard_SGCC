<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function login(Request $request)
    {
        $request->validate([
            'user' => ['required', 'string', 'max:150'],
            'password' => ['required', 'string'],
        ]);

        $credentials = $request->only('user', 'password');

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            if (property_exists($user, 'es_activo') && !$user->es_activo) {
                Auth::logout();
                return back()->withErrors(['user' => 'La cuenta está inactiva.'])->withInput($request->only('user'));
            }

            $user->ultimo_login = now();
            $user->save();

            $request->session()->regenerate();

            return redirect()->intended('/');
        }

        return back()->withErrors(['user' => 'Credenciales inválidas.'])->withInput($request->only('user'));
    }

    /**
     * Log the user out.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
