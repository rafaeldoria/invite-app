<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmailVerificationNotificationController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        if ($request->user()?->hasVerifiedEmail()) {
            return redirect()->route('home');
        }

        $request->user()?->sendEmailVerificationNotification();

        Log::info('security.email_verification.resent', [
            'user_id' => $request->user()?->id,
            'ip' => $request->ip(),
        ]);

        return back()->with('success', __('verification.sent'));
    }
}
