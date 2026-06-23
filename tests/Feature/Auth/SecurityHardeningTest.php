<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_are_added_to_web_responses(): void
    {
        $this->get(route('home'))
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()')
            ->assertHeader('Content-Security-Policy');
    }

    public function test_session_cookie_can_be_marked_secure_for_production(): void
    {
        config()->set('session.secure', true);

        $response = $this->get(route('home'));
        $sessionCookie = collect($response->headers->getCookies())
            ->first(fn ($cookie) => $cookie->getName() === config('session.cookie'));

        $this->assertNotNull($sessionCookie);
        $this->assertTrue($sessionCookie->isSecure());
        $this->assertTrue($sessionCookie->isHttpOnly());
        $this->assertSame('lax', strtolower($sessionCookie->getSameSite()));
    }

    public function test_login_security_logs_do_not_include_secrets(): void
    {
        Log::spy();

        $user = User::factory()->create([
            'email' => 'log@example.com',
            'password' => Hash::make('password-123'),
        ]);

        $this->post(route('login.store'), [
            'email' => 'log@example.com',
            'password' => 'wrong-password',
        ]);

        $this->post(route('login.store'), [
            'email' => 'log@example.com',
            'password' => 'password-123',
        ]);

        Log::shouldHaveReceived('warning')->withArgs(fn (string $event, array $context): bool => $event === 'security.login.failed'
            && array_key_exists('email_hash', $context)
            && ! str_contains(json_encode($context, JSON_THROW_ON_ERROR), 'wrong-password'));

        Log::shouldHaveReceived('info')->withArgs(fn (string $event, array $context): bool => $event === 'security.login.succeeded'
            && ($context['user_id'] ?? null) === $user->id
            && ! str_contains(json_encode($context, JSON_THROW_ON_ERROR), 'password-123'));
    }
}
