<?php

namespace App\Actions\Auth;

use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Support\Facades\Log;

final class LoginUser
{
    public function handle(LoginRequest $request): void
    {
        $request->authenticate();

        $request->session()->regenerate();

        Log::info('security.login.succeeded', [
            'user_id' => $request->user()?->id,
            'ip' => $request->ip(),
        ]);
    }
}
