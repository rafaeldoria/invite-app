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
        RateLimiter::for('locale-preference', function (Request $request) {
            return Limit::perMinute(20)
                ->by($request->ip())
                ->response(fn (Request $request) => $request->header('X-Inertia')
                    ? Inertia::render('Error', ['status' => 429])->toResponse($request)->setStatusCode(429)
                    : response(__('messages.too_many_requests'), 429));
        });
    }
}
