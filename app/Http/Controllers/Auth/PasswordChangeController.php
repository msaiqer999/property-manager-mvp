<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PasswordChangeController extends Controller
{
    public function create()
    {
        return view('auth.change-password');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $request->user()->forceFill([
            'password' => $data['password'],
            'remember_token' => Str::random(60),
        ])->save();

        $request->session()->regenerate();

        return redirect()
            ->route('password.change')
            ->with('status', __('app.auth.password_changed'));
    }
}
