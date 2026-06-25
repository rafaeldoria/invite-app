<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\LoginUser;
use App\Actions\Auth\LogoutUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Support\RedirectsToLocalIntendedUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function __construct(
        private readonly LoginUser $loginUser,
        private readonly LogoutUser $logoutUser,
        private readonly RedirectsToLocalIntendedUrl $redirectsToLocalIntendedUrl,
    ) {}

    public function create(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $this->loginUser->handle($request);

        return $this->redirectsToLocalIntendedUrl->toResponse($request);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->logoutUser->handle($request);

        return redirect()->route('home');
    }
}
