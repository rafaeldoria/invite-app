<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class VerifyEmailController extends Controller
{
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('home')->with('success', __('verification.already_verified'));
        }

        $request->fulfill();

        Log::info('security.email_verification.completed', [
            'user_id' => $request->user()->id,
            'ip' => $request->ip(),
        ]);

        return redirect()->route('home')->with('success', __('verification.verified'));
    }
}
