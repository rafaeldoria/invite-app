<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\CompleteEmailVerification;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    public function __construct(private readonly CompleteEmailVerification $completeEmailVerification) {}

    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('home')->with('success', __('verification.already_verified'));
        }

        $this->completeEmailVerification->handle($request);

        return redirect()->route('home')->with('success', __('verification.verified'));
    }
}
