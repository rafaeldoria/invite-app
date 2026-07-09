<?php

use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetLocale;
use App\Support\Locale;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Vite;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            AddSecurityHeaders::class,
            SetLocale::class,
            HandleInertiaRequests::class,
        ]);
        $middleware->authenticateSessions();
        $middleware->redirectGuestsTo(fn (): string => route('login'));
        $middleware->redirectUsersTo(fn (): string => route('home'));
        $middleware->appendToPriorityList(StartSession::class, SetLocale::class);
        $middleware->prependToPriorityList([ThrottleRequests::class, ThrottleRequestsWithRedis::class], SetLocale::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->respond(function (Response $response, Throwable $exception, Request $request): Response {
            $status = $response->getStatusCode();

            if (! in_array($status, [403, 404, 419, 429, 500, 503], true)) {
                return $response;
            }

            if ($request->is('api/*') || $request->wantsJson()) {
                return $response;
            }

            // Keep detailed local debug pages for server errors while still rendering
            // localized Inertia pages for public 404s and production failures.
            if (config('app.debug') && $status >= 500) {
                return $response;
            }

            $hasSessionLocale = $request->hasSession() && $request->session()->has('locale');
            $localePreference = $hasSessionLocale
                ? $request->session()->get('locale')
                : $request->cookie('locale');

            if (! $hasSessionLocale && (! is_string($localePreference) || ! in_array($localePreference, Locale::supported(), true))) {
                try {
                    $decryptedLocale = app('encrypter')->decrypt((string) $request->cookies->get('locale'), false);
                    $localePreference = CookieValuePrefix::validate('locale', $decryptedLocale, app('encrypter')->getAllKeys());
                } catch (Throwable) {
                    $localePreference = null;
                }
            }

            App::setLocale(Locale::normalize($localePreference));

            Vite::useCspNonce();

            $errorResponse = Inertia::render('Error', [
                'status' => $status,
                'app' => [
                    'name' => config('app.name'),
                ],
                'auth' => [
                    'user' => $request->user()?->only([
                        'id',
                        'name',
                        'email',
                        'email_verified_at',
                    ]),
                ],
                'flash' => [
                    'success' => $request->hasSession() ? $request->session()->pull('success') : null,
                    'error' => $request->hasSession() ? $request->session()->pull('error') : null,
                ],
                'locale' => app()->getLocale(),
            ])
                ->toResponse($request)
                ->setStatusCode($status);

            return app(AddSecurityHeaders::class)->addTo($errorResponse);
        });
    })->create();
