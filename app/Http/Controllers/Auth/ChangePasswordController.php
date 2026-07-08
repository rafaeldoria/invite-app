<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ChangePasswordController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('Settings/Password');
    }

    public function update(ChangePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->forceFill([
            'password' => Hash::make((string) $request->validated('password')),
            'remember_token' => Str::random(60),
        ])->save();

        Password::broker()->deleteToken($user);

        $request->session()->regenerate();

        Log::info('security.password.changed', [
            'user_id' => $user->id,
            'ip' => $request->ip(),
        ]);

        return back()->with('success', __('auth.password_changed'));
    }
}
