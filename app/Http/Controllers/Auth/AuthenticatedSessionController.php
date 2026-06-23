<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        Log::info('security.login.succeeded', [
            'user_id' => $request->user()?->id,
            'ip' => $request->ip(),
        ]);

        return $this->redirectToLocalIntendedUrl($request);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $userId = $request->user()?->id;

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Log::info('security.logout.succeeded', [
            'user_id' => $userId,
            'ip' => $request->ip(),
        ]);

        return redirect()->route('home');
    }

    private function redirectToLocalIntendedUrl(Request $request): RedirectResponse
    {
        $intended = $request->session()->pull('url.intended');

        if (is_string($intended) && $this->isLocalUrl($request, $intended)) {
            return redirect()->to($intended);
        }

        return redirect()->route('home');
    }

    private function isLocalUrl(Request $request, string $url): bool
    {
        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return true;
        }

        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) && $host === $request->getHost();
    }
}
