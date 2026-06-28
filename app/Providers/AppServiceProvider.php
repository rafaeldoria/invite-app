<?php

namespace App\Providers;

use App\Models\Event;
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
            return $this->emailAwareLimits($request, $tooManyAttempts);
        });

        RateLimiter::for('password-reset', function (Request $request) use ($tooManyAttempts) {
            return $this->emailAwareLimits($request, $tooManyAttempts);
        });

        RateLimiter::for('verification-resend', function (Request $request) use ($tooManyAttempts) {
            return Limit::perMinute(3)
                ->by(($request->user()?->id ?? $request->ip()).'|'.$request->ip())
                ->response($tooManyAttempts);
        });

        RateLimiter::for('public-rsvp', function (Request $request) use ($tooManyAttempts) {
            $event = $request->route('event');
            $eventKey = $event instanceof Event ? $event->public_id : (string) ($event ?? 'unknown-event');
            $capability = $request->route('token') ?? $request->input('response_token') ?? 'new-response';

            return [
                Limit::perMinute(60)
                    ->by($eventKey.'|'.$request->ip())
                    ->response($tooManyAttempts),
                Limit::perMinute(12)
                    ->by($eventKey.'|'.$request->ip().'|'.hash('sha256', (string) $capability))
                    ->response($tooManyAttempts),
            ];
        });
    }

    /**
     * @return array<int, Limit>
     */
    private function emailAwareLimits(Request $request, callable $response): array
    {
        $ip = (string) $request->ip();
        $limits = [
            Limit::perMinute(5)
                ->by($ip)
                ->response($response),
        ];

        $email = $request->input('email');

        if (is_string($email)) {
            $limits[] = Limit::perMinute(5)
                ->by($ip.'|'.strtolower(trim($email)))
                ->response($response);
        }

        return $limits;
    }
}
