<?php

namespace Tests\Feature;

use App\Mail\SupportContactSubmitted;
use App\Models\SupportContact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SupportContactTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        RateLimiter::clear('support-contact');

        parent::tearDown();
    }

    public function test_support_contact_page_renders_for_guests_and_prefills_authenticated_user(): void
    {
        $this->get(route('support.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Support/Contact')
                ->where('defaults.name', '')
                ->where('defaults.contact', '')
                ->where('links.store', route('support.store'))
                ->where('links.home', route('home')));

        $user = User::factory()->create([
            'name' => 'Organizer User',
            'email' => 'organizer@example.com',
        ]);

        $this->actingAs($user)
            ->get(route('support.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Support/Contact')
                ->where('defaults.name', 'Organizer User')
                ->where('defaults.contact', 'organizer@example.com'));
    }

    public function test_valid_support_contact_is_saved_and_emailed_to_configured_recipient(): void
    {
        config()->set('support.email', 'support@example.com');
        Mail::fake();

        $this->post(route('support.store'), $this->validPayload())
            ->assertRedirect(route('home'))
            ->assertSessionHas('success', __('support.messages.sent'));

        $contact = SupportContact::query()->firstOrFail();

        $this->assertSame('Ana Organizer', $contact->name);
        $this->assertNotNull($contact->email_sent_at);

        Mail::assertSent(SupportContactSubmitted::class, fn (SupportContactSubmitted $mail): bool => $mail->hasTo('support@example.com')
            && $mail->supportContact->is($contact));
    }

    public function test_authenticated_support_contact_redirects_to_events_after_success(): void
    {
        config()->set('support.email', 'support@example.com');
        Mail::fake();

        $this->actingAs(User::factory()->create())
            ->post(route('support.store'), $this->validPayload())
            ->assertRedirect(route('events.index'))
            ->assertSessionHas('success', __('support.messages.sent'));

        $this->assertDatabaseCount('support_contacts', 1);
    }

    public function test_support_contact_validation_rejects_invalid_payloads(): void
    {
        $this->post(route('support.store'), [
            'name' => '',
            'contact' => 'not-a-contact',
            'subject' => '',
            'message' => '',
        ])->assertSessionHasErrors(['name', 'contact', 'subject', 'message']);

        $this->post(route('support.store'), [
            'name' => str_repeat('a', 121),
            'contact' => str_repeat('a', 256),
            'subject' => str_repeat('a', 161),
            'message' => str_repeat('a', 4001),
        ])->assertSessionHasErrors(['name', 'contact', 'subject', 'message']);
    }

    public function test_support_contact_subject_whitespace_is_normalized_before_emailing(): void
    {
        config()->set('support.email', 'support@example.com');
        Mail::fake();

        $this->post(route('support.store'), $this->validPayload([
            'subject' => "  Help\r\nwith invitation\tsetup  ",
        ]))->assertRedirect(route('home'));

        $this->assertDatabaseHas('support_contacts', [
            'subject' => 'Help with invitation setup',
        ]);

        Mail::assertSent(SupportContactSubmitted::class, fn (SupportContactSubmitted $mail): bool => $mail->supportContact->subject === 'Help with invitation setup');
    }

    public function test_support_contact_message_is_escaped_in_email_body(): void
    {
        config()->set('support.email', 'support@example.com');
        Mail::fake();

        $this->post(route('support.store'), $this->validPayload([
            'message' => '<script>alert("x")</script>',
        ]))->assertRedirect(route('home'));

        Mail::assertSent(SupportContactSubmitted::class, function (SupportContactSubmitted $mail): bool {
            $rendered = $mail->render();

            return str_contains($rendered, '&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;')
                && ! str_contains($rendered, '<script>alert("x")</script>');
        });

        $this->assertDatabaseHas('support_contacts', [
            'message' => '<script>alert("x")</script>',
        ]);
    }

    public function test_support_contact_endpoint_is_throttled(): void
    {
        config()->set('support.email', 'support@example.com');
        Mail::fake();

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post(route('support.store'), $this->validPayload([
                'subject' => 'Support request '.$attempt,
            ]))->assertRedirect(route('home'));
        }

        $this->post(route('support.store'), $this->validPayload([
            'subject' => 'Support request 6',
        ]))->assertTooManyRequests();
    }

    public function test_missing_support_email_saves_contact_and_reports_generic_error(): void
    {
        config()->set('support.email', null);
        Mail::fake();

        $this->post(route('support.store'), $this->validPayload())
            ->assertRedirect(route('support.create'))
            ->assertSessionHas('error', __('support.messages.send_failed'));

        $contact = SupportContact::query()->firstOrFail();

        $this->assertNull($contact->email_sent_at);
        Mail::assertNothingSent();
    }

    /**
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    private function validPayload(array $overrides = []): array
    {
        return [
            'name' => 'Ana Organizer',
            'contact' => 'ana@example.com',
            'subject' => 'Help with invitation setup',
            'message' => 'I need help reviewing the invitation flow.',
            ...$overrides,
        ];
    }
}
