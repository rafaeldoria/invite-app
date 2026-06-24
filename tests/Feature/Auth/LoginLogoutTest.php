<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LoginLogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_rendered(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Auth/Login'));
    }

    public function test_users_can_login_with_normalized_email_and_remember_option(): void
    {
        $user = User::factory()->create([
            'email' => 'organizer@example.com',
            'password' => Hash::make('password-123'),
        ]);

        $this->post(route('login.store'), [
            'email' => ' ORGANIZER@example.com ',
            'password' => 'password-123',
            'remember' => '1',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_regenerates_the_session_id(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password-123'),
        ]);

        $this->get(route('login'));
        $previousSessionId = session()->getId();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password-123',
        ]);

        $this->assertNotSame($previousSessionId, session()->getId());
    }

    public function test_invalid_and_unknown_credentials_use_generic_feedback(): void
    {
        User::factory()->create([
            'email' => 'known@example.com',
            'password' => Hash::make('password-123'),
        ]);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => 'known@example.com',
                'password' => 'wrong-password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => __('auth.failed')]);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => 'unknown@example.com',
                'password' => 'wrong-password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => __('auth.failed')]);

        $this->assertGuest();
    }

    public function test_login_rate_limit_uses_email_and_ip_key(): void
    {
        User::factory()->create([
            'email' => 'limited@example.com',
            'password' => Hash::make('password-123'),
        ]);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson(route('login.store'), [
                'email' => 'limited@example.com',
                'password' => 'wrong-password',
            ])->assertUnprocessable();
        }

        $this->postJson(route('login.store'), [
            'email' => 'limited@example.com',
            'password' => 'wrong-password',
        ])->assertTooManyRequests();
    }

    public function test_login_rate_limit_uses_ip_key_when_email_changes(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson(route('login.store'), [
                'email' => "limited-{$attempt}@example.com",
                'password' => 'wrong-password',
            ])->assertUnprocessable();
        }

        $this->postJson(route('login.store'), [
            'email' => 'limited@example.com',
            'password' => 'wrong-password',
        ])->assertTooManyRequests();
    }

    public function test_external_intended_redirects_are_rejected_after_login(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password-123'),
        ]);

        $this->withSession(['url.intended' => 'https://evil.example/phishing'])
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'password-123',
            ])
            ->assertRedirect(route('home'));
    }

    public function test_logout_invalidates_the_authenticated_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('home'));

        $this->assertGuest();
    }

    public function test_guest_cannot_use_logout_route(): void
    {
        $this->post(route('logout'))
            ->assertRedirect(route('login'));
    }
}
