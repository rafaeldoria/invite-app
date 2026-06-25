<?php

namespace App\Actions\Auth;

use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class LoginUser
{
    /**
     * @throws ValidationException
     */
    public function handle(LoginRequest $request): void
    {
        $this->ensureIsNotRateLimited($request);

        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            foreach ($this->throttleKeys($request) as $key) {
                RateLimiter::hit($key);
            }

            Log::warning('security.login.failed', [
                'email_hash' => hash('sha256', (string) $request->input('email')),
                'ip' => $request->ip(),
            ]);

            $this->failAuthentication($request, __('auth.failed'));
        }

        foreach ($this->throttleKeys($request) as $key) {
            RateLimiter::clear($key);
        }

        $request->session()->regenerate();

        Log::info('security.login.succeeded', [
            'user_id' => $request->user()?->id,
            'ip' => $request->ip(),
        ]);
    }

    /**
     * @throws ValidationException
     */
    private function ensureIsNotRateLimited(LoginRequest $request): void
    {
        foreach ($this->throttleKeys($request) as $key) {
            if (! RateLimiter::tooManyAttempts($key, 5)) {
                continue;
            }

            event(new Lockout($request));

            $seconds = RateLimiter::availableIn($key);

            $this->failAuthentication($request, trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]), 429);
        }
    }

    /**
     * @return array<int, string>
     */
    private function throttleKeys(LoginRequest $request): array
    {
        $ip = (string) $request->ip();

        return [
            Str::transliterate(Str::lower((string) $request->input('email')).'|'.$ip),
            $ip,
        ];
    }

    /**
     * @throws ValidationException
     */
    private function failAuthentication(LoginRequest $request, string $message, int $status = 422): never
    {
        if ($request->expectsJson()) {
            throw new HttpResponseException(response()->json([
                'message' => $message,
                'errors' => [
                    'email' => [$message],
                ],
            ], $status));
        }

        throw ValidationException::withMessages([
            'email' => $message,
        ])->status($status);
    }
}
