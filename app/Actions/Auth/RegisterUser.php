<?php

namespace App\Actions\Auth;

use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

final class RegisterUser
{
    public function handle(RegisterRequest $request): void
    {
        /** @var array{name: string, email: string, password: string} $validated */
        $validated = $request->validated();

        $user = User::query()->create($validated);

        event(new Registered($user));

        Auth::login($user);

        $request->session()->regenerate();

        Log::info('security.registration.created', [
            'user_id' => $user->id,
            'ip' => $request->ip(),
        ]);
    }
}
