<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_page_is_rendered(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Auth/ForgotPassword'));
    }

    public function test_password_reset_link_response_is_generic_for_known_and_unknown_accounts(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'known@example.com']);

        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'known@example.com'])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('success', __('passwords.sent'));

        Notification::assertSentTo($user, ResetPassword::class);

        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'unknown@example.com'])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('success', __('passwords.sent'));
    }

    public function test_password_reset_request_is_rate_limited(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post(route('password.email'), ['email' => 'rate@example.com'])
                ->assertRedirect();
        }

        $this->post(route('password.email'), ['email' => 'rate@example.com'])
            ->assertTooManyRequests();
    }

    public function test_reset_password_page_is_rendered_with_token_and_email(): void
    {
        $this->get(route('password.reset', ['token' => 'reset-token', 'email' => 'guest@example.com']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/ResetPassword')
                ->where('token', 'reset-token')
                ->where('email', 'guest@example.com'));
    }

    public function test_password_can_be_reset_and_token_cannot_be_replayed(): void
    {
        $user = User::factory()->create([
            'email' => 'reset@example.com',
            'password' => Hash::make('old-password'),
        ]);
        $token = Password::broker()->createToken($user);

        $this->from(route('password.reset', ['token' => $token]))
            ->post(route('password.store'), [
                'token' => $token,
                'email' => ' RESET@example.com ',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHas('success', __('passwords.reset'));

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));

        $this->from(route('password.reset', ['token' => $token]))
            ->post(route('password.store'), [
                'token' => $token,
                'email' => 'reset@example.com',
                'password' => 'another-password',
                'password_confirmation' => 'another-password',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }

    public function test_invalid_reset_token_is_rejected(): void
    {
        User::factory()->create(['email' => 'reset@example.com']);

        $this->from(route('password.reset', ['token' => 'invalid-token']))
            ->post(route('password.store'), [
                'token' => 'invalid-token',
                'email' => 'reset@example.com',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('email');
    }
}
