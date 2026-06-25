<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_page_is_rendered(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Auth/Register'));
    }

    public function test_new_users_can_register_and_receive_verification_email(): void
    {
        Notification::fake();

        $response = $this->post(route('register.store'), [
            'name' => 'Ada Lovelace',
            'email' => ' ADA@example.com ',
            'password' => 'password-123',
            'password_confirmation' => 'password-123',
        ]);

        $user = User::query()->where('email', 'ada@example.com')->firstOrFail();

        $response->assertRedirect(route('verification.notice'));
        $this->assertAuthenticatedAs($user);
        $this->assertSame('Ada Lovelace', $user->name);
        $this->assertTrue(Hash::check('password-123', $user->password));
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_duplicate_email_registration_uses_normalized_email(): void
    {
        User::factory()->create(['email' => 'guest@example.com']);

        $this->from(route('register'))
            ->post(route('register.store'), [
                'name' => 'Guest User',
                'email' => ' GUEST@example.com ',
                'password' => 'password-123',
                'password_confirmation' => 'password-123',
            ])
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('email');

        $this->assertSame(1, User::query()->where('email', 'guest@example.com')->count());
    }

    public function test_registration_is_rate_limited(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post(route('register.store'), [
                'name' => '',
                'email' => "rate-{$attempt}@example.com",
                'password' => '',
                'password_confirmation' => '',
            ])->assertSessionHasErrors(['name', 'password']);
        }

        $this->post(route('register.store'), [
            'name' => '',
            'email' => 'rate@example.com',
            'password' => '',
            'password_confirmation' => '',
        ])->assertTooManyRequests();
    }

    public function test_malformed_email_payload_does_not_break_registration_throttle(): void
    {
        $this->from(route('register'))
            ->post(route('register.store'), [
                'name' => 'Guest User',
                'email' => ['guest@example.com'],
                'password' => 'password-123',
                'password_confirmation' => 'password-123',
            ])
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('email');
    }

    public function test_registration_rejects_passwords_longer_than_bcrypt_limit(): void
    {
        $longPassword = str_repeat('a', 73);

        $this->from(route('register'))
            ->post(route('register.store'), [
                'name' => 'Guest User',
                'email' => 'guest@example.com',
                'password' => $longPassword,
                'password_confirmation' => $longPassword,
            ])
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'guest@example.com']);
    }

    public function test_registration_rejects_multibyte_passwords_longer_than_bcrypt_byte_limit(): void
    {
        $longPassword = str_repeat("\xF0\x9F\x98\x80", 19);

        $this->from(route('register'))
            ->post(route('register.store'), [
                'name' => 'Guest User',
                'email' => 'guest@example.com',
                'password' => $longPassword,
                'password_confirmation' => $longPassword,
            ])
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'guest@example.com']);
    }

    public function test_validation_messages_follow_active_locale(): void
    {
        $this->withSession(['locale' => 'en-US'])
            ->from(route('register'))
            ->post(route('register.store'), [])
            ->assertSessionHasErrors(['name' => 'The name field is required.']);

        $this->withSession(['locale' => 'pt-BR'])
            ->from(route('register'))
            ->post(route('register.store'), [])
            ->assertSessionHasErrors(['name' => 'O campo name é obrigatório.']);
    }
}
