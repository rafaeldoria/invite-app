<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $tooManyAttempts = fn (Request $request) => $request->header('X-Inertia')
            ? Inertia::render('Error', ['status' => 429])->toResponse($request)->setStatusCode(429)
            : response(__('messages.too_many_requests'), 429);

        RateLimiter::for('locale-preference', function (Request $request) {
            return Limit::perMinute(20)
                ->by($request->ip())
                ->response(fn (Request $request) => $request->header('X-Inertia')
                    ? Inertia::render('Error', ['status' => 429])->toResponse($request)->setStatusCode(429)
                    : response(__('messages.too_many_requests'), 429));
        });

        RateLimiter::for('auth-registration', function (Request $request) use ($tooManyAttempts) {
            return [
                Limit::perMinute(5)
                    ->by($request->ip())
                    ->response($tooManyAttempts),
                Limit::perMinute(5)
                    ->by($request->ip().'|'.strtolower((string) $request->input('email')))
                    ->response($tooManyAttempts),
            ];
        });

        RateLimiter::for('password-reset', function (Request $request) use ($tooManyAttempts) {
            return Limit::perMinute(5)
                ->by($request->ip().'|'.strtolower((string) $request->input('email')))
                ->response($tooManyAttempts);
        });

        RateLimiter::for('verification-resend', function (Request $request) use ($tooManyAttempts) {
            return Limit::perMinute(3)
                ->by(($request->user()?->id ?? $request->ip()).'|'.$request->ip())
                ->response($tooManyAttempts);
        });
    }
}
