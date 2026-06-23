<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_verification_notice_requires_authentication(): void
    {
        $this->get(route('verification.notice'))
            ->assertRedirect(route('login'));
    }

    public function test_unverified_user_can_view_verification_notice(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('verification.notice'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Auth/VerifyEmail'));
    }

    public function test_verified_user_is_redirected_away_from_verification_notice(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('verification.notice'))
            ->assertRedirect(route('home'));
    }

    public function test_valid_signed_verification_link_marks_email_as_verified(): void
    {
        $user = User::factory()->unverified()->create();
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(10), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->actingAs($user)
            ->get($url)
            ->assertRedirect(route('home'));

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_tampered_and_expired_verification_links_are_rejected(): void
    {
        $user = User::factory()->unverified()->create();
        $validUrl = URL::temporarySignedRoute('verification.verify', now()->addMinutes(10), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->actingAs($user)
            ->get($validUrl.'&signature=tampered')
            ->assertForbidden();

        Carbon::setTestNow(now());
        $expiredUrl = URL::temporarySignedRoute('verification.verify', now()->subMinute(), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->actingAs($user)
            ->get($expiredUrl)
            ->assertForbidden();

        Carbon::setTestNow();
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_verification_resend_sends_notification_and_is_throttled(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $this->actingAs($user)
                ->post(route('verification.send'))
                ->assertRedirect();
        }

        Notification::assertSentTo($user, VerifyEmail::class, 3);

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertTooManyRequests();
    }

    public function test_verified_middleware_blocks_unverified_mutations(): void
    {
        Route::post('/auth-test/protected-mutation', fn () => response('ok'))
            ->middleware(['web', 'auth', 'verified']);

        $this->post('/auth-test/protected-mutation')
            ->assertRedirect(route('login'));

        $this->actingAs(User::factory()->unverified()->create())
            ->post('/auth-test/protected-mutation')
            ->assertRedirect(route('verification.notice'));

        $this->actingAs(User::factory()->create())
            ->post('/auth-test/protected-mutation')
            ->assertOk();
    }
}
