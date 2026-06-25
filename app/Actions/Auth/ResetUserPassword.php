<?php

namespace App\Actions\Auth;

use App\Http\Requests\Auth\ResetPasswordRequest;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ResetUserPassword
{
    public function handle(ResetPasswordRequest $request): string
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request): void {
                $user->forceFill([
                    'password' => Hash::make((string) $request->input('password')),
                    'remember_token' => Str::random(60),
                ])->save();

                $this->revokeDatabaseSessions((int) $user->getAuthIdentifier());

                event(new PasswordReset($user));

                Log::info('security.password_reset.completed', [
                    'user_id' => $user->id,
                    'ip' => $request->ip(),
                ]);
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => __('passwords.token'),
            ]);
        }

        return $status;
    }

    private function revokeDatabaseSessions(int $userId): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        DB::connection(config('session.connection'))
            ->table((string) config('session.table', 'sessions'))
            ->where('user_id', $userId)
            ->delete();
    }
}
