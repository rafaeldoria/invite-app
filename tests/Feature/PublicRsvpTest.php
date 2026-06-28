<?php

namespace Tests\Feature;

use App\Actions\Rsvp\SubmitPublicRsvp;
use App\Enums\GuestStatus;
use App\Models\Event;
use App\Models\Guest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicRsvpTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        RateLimiter::clear('public-rsvp');

        parent::tearDown();
    }

    public function test_general_guest_can_confirm_and_update_with_management_capability(): void
    {
        Carbon::setTestNow('2030-01-01 12:00:00');
        $event = Event::factory()->create();
        $token = Str::random(64);

        $this->get(route('public.rsvp.create', $event))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Rsvp/Form')
                ->where('rsvp.mode', 'general')
                ->where('rsvp.name_locked', false)
                ->where('rsvp.submit_url', route('public.rsvp.store', $event))
                ->where('rsvp.method', 'post')
                ->where('rsvp.event_url', route('public.events.show', $event))
                ->where('rsvp.receipt', null)
                ->missing('guest')
                ->missing('guests'));

        $this->post(route('public.rsvp.store', $event), [
            'name' => '  Sam Guest  ',
            'attendance' => GuestStatus::Confirmed->value,
            'adult_companions' => 2,
            'child_companions' => 1,
            'response_token' => $token,
        ])->assertRedirect(route('public.rsvp.show', [$event, $token]));

        $guest = Guest::query()->firstOrFail();
        $this->assertSame('Sam Guest', $guest->name);
        $this->assertSame(GuestStatus::Confirmed, $guest->status);
        $this->assertSame(2, $guest->adult_companions);
        $this->assertSame(1, $guest->child_companions);
        $this->assertSame(hash('sha256', $token), $guest->response_token_hash);
        $this->assertNotSame($token, $guest->response_token_hash);
        $this->assertNotNull($guest->responded_at);

        $this->get(route('public.rsvp.show', [$event, $token]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Rsvp/Form')
                ->where('rsvp.mode', 'management')
                ->where('rsvp.name_locked', true)
                ->where('rsvp.guest_name', 'Sam Guest')
                ->where('rsvp.receipt.name', 'Sam Guest')
                ->where('rsvp.receipt.status', 'confirmed')
                ->where('rsvp.receipt.companion_count', 3)
                ->where('rsvp.receipt.party_size', 4)
                ->missing('rsvp.receipt.invitation_token')
                ->missing('rsvp.receipt.response_token_hash')
                ->missing('event.owner')
                ->missing('event.user_id'));

        Carbon::setTestNow('2030-01-01 12:05:00');

        $this->patch(route('public.rsvp.update', [$event, $token]), [
            'attendance' => GuestStatus::Declined->value,
            'adult_companions' => 2,
            'child_companions' => 1,
        ])->assertRedirect(route('public.rsvp.show', [$event, $token]));

        $guest->refresh();
        $this->assertSame(GuestStatus::Declined, $guest->status);
        $this->assertSame(0, $guest->adult_companions);
        $this->assertSame(0, $guest->child_companions);
        $this->assertSame('2030-01-01 12:05:00', $guest->responded_at->format('Y-m-d H:i:s'));
    }

    public function test_general_decline_and_replay_do_not_create_duplicate_guests(): void
    {
        $event = Event::factory()->create();
        $token = Str::random(64);
        $payload = [
            'name' => 'Jordan Guest',
            'attendance' => GuestStatus::Declined->value,
            'adult_companions' => 20,
            'child_companions' => 20,
            'response_token' => $token,
        ];

        $this->post(route('public.rsvp.store', $event), $payload)->assertRedirect();
        $this->post(route('public.rsvp.store', $event), $payload)->assertRedirect();

        $this->assertSame(1, Guest::query()->where('response_token_hash', hash('sha256', $token))->count());

        $guest = Guest::query()->firstOrFail();
        $this->assertSame(GuestStatus::Declined, $guest->status);
        $this->assertSame(0, $guest->adult_companions);
        $this->assertSame(0, $guest->child_companions);
    }

    public function test_general_replay_does_not_rename_existing_guest(): void
    {
        $event = Event::factory()->create();
        $token = Str::random(64);

        $this->post(route('public.rsvp.store', $event), [
            'name' => 'Original Guest',
            'attendance' => GuestStatus::Confirmed->value,
            'adult_companions' => 1,
            'child_companions' => 0,
            'response_token' => $token,
        ])->assertRedirect();

        $this->post(route('public.rsvp.store', $event), [
            'name' => 'Renamed Guest',
            'attendance' => GuestStatus::Declined->value,
            'adult_companions' => 0,
            'child_companions' => 0,
            'response_token' => $token,
        ])->assertRedirect();

        $guest = Guest::query()->firstOrFail();

        $this->assertSame('Original Guest', $guest->name);
        $this->assertSame(GuestStatus::Declined, $guest->status);
        $this->assertSame(1, Guest::query()->count());
    }

    public function test_individual_invitation_updates_existing_guest_without_creating_another(): void
    {
        $event = Event::factory()->create();
        $guest = Guest::factory()->for($event)->pending()->create(['name' => 'Invited Guest']);

        $this->get(route('public.invitations.show', [$event, $guest->invitation_token]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('PublicEvent/Show')
                ->where('event.rsvp.url', route('public.invitations.rsvp.edit', [$event, $guest->invitation_token]))
                ->missing('guest'));

        $this->get(route('public.invitations.rsvp.edit', [$event, $guest->invitation_token]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Rsvp/Form')
                ->where('rsvp.mode', 'invitation')
                ->where('rsvp.name_locked', true)
                ->where('rsvp.event_url', route('public.invitations.show', [$event, $guest->invitation_token]))
                ->where('rsvp.guest_name', 'Invited Guest')
                ->where('rsvp.receipt', null)
                ->missing('guest.id')
                ->missing('guest.invitation_token'));

        $this->patch(route('public.invitations.rsvp.update', [$event, $guest->invitation_token]), [
            'attendance' => GuestStatus::Confirmed->value,
            'adult_companions' => 0,
            'child_companions' => 20,
        ])->assertRedirect(route('public.invitations.rsvp.edit', [$event, $guest->invitation_token]));

        $guest->refresh();
        $this->assertSame(1, Guest::query()->count());
        $this->assertSame(GuestStatus::Confirmed, $guest->status);
        $this->assertSame(0, $guest->adult_companions);
        $this->assertSame(20, $guest->child_companions);

        $this->get(route('public.invitations.rsvp.edit', [$event, $guest->invitation_token]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('rsvp.receipt.status', 'confirmed')
                ->where('rsvp.receipt.party_size', 21));
    }

    public function test_name_collisions_create_separate_general_responses(): void
    {
        $event = Event::factory()->create();

        $this->post(route('public.rsvp.store', $event), [
            'name' => 'Same Name',
            'attendance' => GuestStatus::Confirmed->value,
            'adult_companions' => 0,
            'child_companions' => 0,
            'response_token' => Str::random(64),
        ])->assertRedirect();

        $this->post(route('public.rsvp.store', $event), [
            'name' => 'Same Name',
            'attendance' => GuestStatus::Declined->value,
            'adult_companions' => 0,
            'child_companions' => 0,
            'response_token' => Str::random(64),
        ])->assertRedirect();

        $this->assertSame(2, Guest::query()->where('name', 'Same Name')->count());
    }

    public function test_validation_boundaries_and_status_switch_invariants_are_enforced(): void
    {
        $event = Event::factory()->create();
        $token = Str::random(64);

        $this->post(route('public.rsvp.store', $event), [
            'name' => '',
            'attendance' => 'pending',
            'adult_companions' => -1,
            'child_companions' => 21,
            'response_token' => 'short',
        ])->assertSessionHasErrors(['name', 'attendance', 'adult_companions', 'child_companions', 'response_token']);

        $this->post(route('public.rsvp.store', $event), [
            'name' => 'Boundary Guest',
            'attendance' => GuestStatus::Confirmed->value,
            'adult_companions' => 0,
            'child_companions' => 20,
            'response_token' => $token,
        ])->assertRedirect();

        $guest = Guest::query()->firstOrFail();
        $this->assertSame(0, $guest->adult_companions);
        $this->assertSame(20, $guest->child_companions);

        $this->patch(route('public.rsvp.update', [$event, $token]), [
            'attendance' => GuestStatus::Declined->value,
            'adult_companions' => 20,
            'child_companions' => 20,
        ])->assertRedirect();

        $guest->refresh();
        $this->assertSame(0, $guest->adult_companions);
        $this->assertSame(0, $guest->child_companions);

        $this->patch(route('public.rsvp.update', [$event, $token]), [
            'attendance' => GuestStatus::Confirmed->value,
            'adult_companions' => '1.5',
            'child_companions' => 'one',
        ])->assertSessionHasErrors(['adult_companions', 'child_companions']);
    }

    public function test_tampered_unknown_and_mismatched_capabilities_are_not_found(): void
    {
        $event = Event::factory()->create();
        $otherEvent = Event::factory()->create();
        $token = Str::random(64);

        $this->post(route('public.rsvp.store', $event), [
            'name' => 'Private Guest',
            'attendance' => GuestStatus::Confirmed->value,
            'adult_companions' => 0,
            'child_companions' => 0,
            'response_token' => $token,
        ])->assertRedirect();

        $this->get(route('public.rsvp.show', [$event, 'tampered-'.$token]))->assertNotFound();
        $this->get(route('public.rsvp.show', [$otherEvent, $token]))->assertNotFound();

        $guest = Guest::factory()->for($event)->create();

        $this->get(route('public.invitations.rsvp.edit', [$otherEvent, $guest->invitation_token]))->assertNotFound();
        $this->patch(route('public.invitations.rsvp.update', [$otherEvent, $guest->invitation_token]), [
            'attendance' => GuestStatus::Declined->value,
            'adult_companions' => 0,
            'child_companions' => 0,
        ])->assertNotFound();
    }

    public function test_public_rsvp_pages_are_not_shared_cacheable(): void
    {
        $event = Event::factory()->create();
        $token = Str::random(64);

        $create = $this->get(route('public.rsvp.create', $event))->assertOk();
        $this->assertStringContainsString('private', $create->headers->get('Cache-Control') ?? '');
        $this->assertStringContainsString('no-store', $create->headers->get('Cache-Control') ?? '');

        $this->post(route('public.rsvp.store', $event), [
            'name' => 'Cache Guest',
            'attendance' => GuestStatus::Confirmed->value,
            'adult_companions' => 0,
            'child_companions' => 0,
            'response_token' => $token,
        ])->assertRedirect();

        $show = $this->get(route('public.rsvp.show', [$event, $token]))->assertOk();
        $this->assertStringContainsString('private', $show->headers->get('Cache-Control') ?? '');
        $this->assertStringContainsString('no-store', $show->headers->get('Cache-Control') ?? '');
    }

    public function test_public_rsvp_rate_limit_uses_event_and_capability(): void
    {
        config()->set('app.env', 'production');

        $event = Event::factory()->create();
        $token = Str::random(64);

        for ($attempt = 0; $attempt < 12; $attempt++) {
            $this->from(route('public.rsvp.create', $event))
                ->post(route('public.rsvp.store', $event), [
                    'name' => 'Limited Guest',
                    'attendance' => GuestStatus::Confirmed->value,
                    'adult_companions' => 0,
                    'child_companions' => 0,
                    'response_token' => $token,
                ])->assertRedirect();
        }

        $this->from(route('public.rsvp.create', $event))
            ->post(route('public.rsvp.store', $event), [
                'name' => 'Limited Guest',
                'attendance' => GuestStatus::Confirmed->value,
                'adult_companions' => 0,
                'child_companions' => 0,
                'response_token' => $token,
            ])->assertTooManyRequests();

        $this->from(route('public.rsvp.create', $event))
            ->post(route('public.rsvp.store', $event), [
                'name' => 'Another Household',
                'attendance' => GuestStatus::Confirmed->value,
                'adult_companions' => 0,
                'child_companions' => 0,
                'response_token' => Str::random(64),
            ])->assertRedirect();
    }

    public function test_public_rsvp_event_rate_limit_is_scoped_per_event(): void
    {
        config()->set('app.env', 'production');

        $event = Event::factory()->create();
        $otherEvent = Event::factory()->create();

        for ($attempt = 0; $attempt < 60; $attempt++) {
            $this->from(route('public.rsvp.create', $event))
                ->post(route('public.rsvp.store', $event), [
                    'name' => 'Limited Guest '.$attempt,
                    'attendance' => GuestStatus::Confirmed->value,
                    'adult_companions' => 0,
                    'child_companions' => 0,
                    'response_token' => Str::random(64),
                ])->assertRedirect();
        }

        $this->from(route('public.rsvp.create', $event))
            ->post(route('public.rsvp.store', $event), [
                'name' => 'Blocked Guest',
                'attendance' => GuestStatus::Confirmed->value,
                'adult_companions' => 0,
                'child_companions' => 0,
                'response_token' => Str::random(64),
            ])->assertTooManyRequests();

        $this->from(route('public.rsvp.create', $otherEvent))
            ->post(route('public.rsvp.store', $otherEvent), [
                'name' => 'Other Event Guest',
                'attendance' => GuestStatus::Confirmed->value,
                'adult_companions' => 0,
                'child_companions' => 0,
                'response_token' => Str::random(64),
            ])->assertRedirect();
    }

    public function test_response_tokens_are_hashed_by_the_domain_operation(): void
    {
        $event = Event::factory()->create();
        $token = Str::random(64);

        app(SubmitPublicRsvp::class)->createFromGeneralLink($event, $token, [
            'name' => 'Hashed Guest',
            'attendance' => GuestStatus::Confirmed->value,
            'adult_companions' => 0,
            'child_companions' => 0,
        ]);

        $this->assertDatabaseHas('guests', [
            'name' => 'Hashed Guest',
            'response_token_hash' => hash('sha256', $token),
        ]);
        $this->assertDatabaseMissing('guests', [
            'response_token_hash' => $token,
        ]);
    }
}
