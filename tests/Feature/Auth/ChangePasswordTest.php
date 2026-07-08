<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_change_password_page_requires_authentication(): void
    {
        $this->get(route('settings.password.edit'))
            ->assertRedirect(route('login'));
    }

    public function test_change_password_page_is_rendered_for_authenticated_users(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.password.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Settings/Password'));
    }

    public function test_authenticated_user_can_change_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);

        $this->actingAs($user)
            ->patch(route('settings.password.update'), [
                'current_password' => 'old-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', __('auth.password_changed'));

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }

    public function test_password_change_clears_outstanding_reset_tokens(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);
        $token = Password::broker()->createToken($user);

        $this->assertTrue(Password::broker()->tokenExists($user, $token));

        $this->actingAs($user)
            ->patch(route('settings.password.update'), [
                'current_password' => 'old-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertRedirect();

        $this->assertFalse(Password::broker()->tokenExists($user->refresh(), $token));
    }

    public function test_current_password_must_match(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);

        $this->actingAs($user)
            ->from(route('settings.password.edit'))
            ->patch(route('settings.password.update'), [
                'current_password' => 'wrong-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertRedirect(route('settings.password.edit'))
            ->assertSessionHasErrors(['current_password']);

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_password_change_attempts_are_rate_limited(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.55'])
                ->actingAs($user)
                ->from(route('settings.password.edit'))
                ->patch(route('settings.password.update'), [
                    'current_password' => 'wrong-password',
                    'password' => 'new-password',
                    'password_confirmation' => 'new-password',
                ])
                ->assertRedirect(route('settings.password.edit'));
        }

        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.55'])
            ->actingAs($user)
            ->patch(route('settings.password.update'), [
                'current_password' => 'wrong-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertTooManyRequests();
    }
}
