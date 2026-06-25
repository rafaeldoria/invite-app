<?php

namespace App\Actions\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

final class LogoutUser
{
    public function handle(Request $request): void
    {
        $userId = $request->user()?->id;

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Log::info('security.logout.succeeded', [
            'user_id' => $userId,
            'ip' => $request->ip(),
        ]);
    }
}
