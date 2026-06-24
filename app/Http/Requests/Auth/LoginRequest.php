<?php

namespace App\Http\Requests\Auth;

use App\Support\EmailAddress;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => EmailAddress::normalize($this->input('email')),
            'remember' => $this->boolean('remember'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            foreach ($this->throttleKeys() as $key) {
                RateLimiter::hit($key);
            }

            Log::warning('security.login.failed', [
                'email_hash' => hash('sha256', (string) $this->input('email')),
                'ip' => $this->ip(),
            ]);

            $this->failAuthentication(__('auth.failed'));
        }

        foreach ($this->throttleKeys() as $key) {
            RateLimiter::clear($key);
        }
    }

    /**
     * @throws ValidationException
     */
    private function ensureIsNotRateLimited(): void
    {
        foreach ($this->throttleKeys() as $key) {
            if (! RateLimiter::tooManyAttempts($key, 5)) {
                continue;
            }

            event(new Lockout($this));

            $seconds = RateLimiter::availableIn($key);

            $this->failAuthentication(trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]), 429);
        }
    }

    /**
     * @return array<int, string>
     */
    private function throttleKeys(): array
    {
        $ip = (string) $this->ip();

        return [
            Str::transliterate(Str::lower((string) $this->input('email')).'|'.$ip),
            $ip,
        ];
    }

    /**
     * @throws ValidationException
     */
    private function failAuthentication(string $message, int $status = 422): never
    {
        if ($this->expectsJson()) {
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
