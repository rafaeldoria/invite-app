<?php

namespace App\Actions\Auth;

use App\Http\Requests\Auth\ForgotPasswordRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;

final class SendPasswordResetLink
{
    public function handle(ForgotPasswordRequest $request): void
    {
        Password::sendResetLink($request->only('email'));

        Log::info('security.password_reset.requested', [
            'email_hash' => hash('sha256', (string) $request->input('email')),
            'ip' => $request->ip(),
        ]);
    }
}
