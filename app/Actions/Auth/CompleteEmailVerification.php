<?php

namespace App\Actions\Auth;

use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Log;

final class CompleteEmailVerification
{
    public function handle(EmailVerificationRequest $request): void
    {
        $request->fulfill();

        Log::info('security.email_verification.completed', [
            'user_id' => $request->user()->id,
            'ip' => $request->ip(),
        ]);
    }
}
