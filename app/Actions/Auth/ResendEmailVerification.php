<?php

namespace App\Actions\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class ResendEmailVerification
{
    public function handle(Request $request): void
    {
        $request->user()?->sendEmailVerificationNotification();

        Log::info('security.email_verification.resent', [
            'user_id' => $request->user()?->id,
            'ip' => $request->ip(),
        ]);
    }
}
