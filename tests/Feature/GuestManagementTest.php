<?php

namespace Tests\Feature;

use App\Enums\GuestStatus;
use App\Models\Event;
use App\Models\Guest;
use App\Models\GuestCompanion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GuestManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_pages_require_a_verified_organizer(): void
    {
        $event = Event::factory()->create();

        $this->get(route('events.guests.index', $event))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->unverified()->create())
            ->get(route('events.guests.index', $event))
            ->assertRedirect(route('verification.notice'));

        $this->actingAs($event->owner)
            ->get(route('events.guests.index', $event))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Guests/Index')
                ->has('guests.data', 0)
                ->where('guests.total', 0));
    }

    public function test_owner_can_create_list_update_and_delete_guests(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->for($user, 'owner')->create();

        $this->actingAs($user)
            ->post(route('events.guests.store', $event), [
                'name' => '  Alex Guest  ',
                'invitation_token' => 'attacker-controlled-token',
            ])
            ->assertRedirect(route('events.guests.index', $event));

        $guest = Guest::firstOrFail();

        $this->assertSame('Alex Guest', $guest->name);
        $this->assertSame(GuestStatus::Pending, $guest->status);
        $this->assertSame(0, $guest->adult_companions);
        $this->assertSame(0, $guest->child_companions);
        $this->assertNotSame('attacker-controlled-token', $guest->invitation_token);

        $this->actingAs($user)
            ->get(route('events.guests.index', $event))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Guests/Index')
                ->where('event.name', $event->name)
                ->has('guests.data', 1)
                ->where('guests.data.0.name', 'Alex Guest')
                ->where('guests.data.0.invitation_url', route('public.invitations.show', [$event, $guest->invitation_token]))
                ->where('guests.data.0.status', 'pending')
                ->where('guests.data.0.companion_count', 0)
                ->has('guests.data.0.companions', 0)
                ->missing('guests.data.0.id')
                ->missing('guests.data.0.invitation_token')
                ->missing('guests.data.0.response_token_hash'));

        $this->actingAs($user)
            ->patch(route('events.guests.update', [$event, $guest]), [
                'name' => 'Alex Updated',
                'status' => GuestStatus::Confirmed->value,
                'adult_companions' => 2,
                'child_companions' => 1,
                'invitation_token' => 'another-attacker-token',
            ])
            ->assertRedirect();

        $guest->refresh();
        $this->assertSame('Alex Updated', $guest->name);
        $this->assertSame(GuestStatus::Confirmed, $guest->status);
        $this->assertSame(2, $guest->adult_companions);
        $this->assertSame(1, $guest->child_companions);
        $this->assertNotSame('another-attacker-token', $guest->invitation_token);
        $this->assertNotNull($guest->responded_at);

        $this->actingAs($user)
            ->delete(route('events.guests.destroy', [$event, $guest]))
            ->assertRedirect();

        $this->assertDatabaseMissing('guests', ['id' => $guest->id]);
    }

    public function test_cross_owner_and_cross_event_access_is_denied(): void
    {
        $owner = User::factory()->create();
        $otherOrganizer = User::factory()->create();
        $event = Event::factory()->for($owner, 'owner')->create();
        $otherEvent = Event::factory()->for($owner, 'owner')->create();
        $guest = Guest::factory()->for($event)->create();

        $this->actingAs($otherOrganizer)
            ->get(route('events.guests.index', $event))
            ->assertForbidden();

        $this->actingAs($otherOrganizer)
            ->post(route('events.guests.store', $event), ['name' => 'Blocked Guest'])
            ->assertForbidden();

        $this->actingAs($otherOrganizer)
            ->patch(route('events.guests.update', [$event, $guest]), $this->validPayload())
            ->assertForbidden();

        $this->actingAs($owner)
            ->patch(route('events.guests.update', [$otherEvent, $guest]), $this->validPayload())
            ->assertNotFound();

        $this->actingAs($owner)
            ->delete(route('events.guests.destroy', [$otherEvent, $guest]))
            ->assertNotFound();
    }

    public function test_validation_allows_duplicate_names_and_enforces_boundaries(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->for($user, 'owner')->create();

        $this->actingAs($user)
            ->post(route('events.guests.store', $event), ['name' => ''])
            ->assertSessionHasErrors(['name']);

        $this->actingAs($user)
            ->post(route('events.guests.store', $event), ['name' => str_repeat('a', 121)])
            ->assertSessionHasErrors(['name']);

        $this->actingAs($user)
            ->post(route('events.guests.store', $event), ['name' => 'Sam Guest'])
            ->assertRedirect();

        $this->actingAs($user)
            ->post(route('events.guests.store', $event), ['name' => 'Sam Guest'])
            ->assertRedirect();

        $this->assertSame(2, Guest::query()->where('name', 'Sam Guest')->count());

        $guest = Guest::firstOrFail();

        $this->actingAs($user)
            ->patch(route('events.guests.update', [$event, $guest]), [
                'name' => 'Sam Guest',
                'status' => 'unknown',
                'adult_companions' => -1,
                'child_companions' => 6,
            ])
            ->assertSessionHasErrors(['status', 'adult_companions', 'child_companions']);
    }

    public function test_status_count_invariants_match_rsvp_contract(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->for($user, 'owner')->create();
        $guest = Guest::factory()->for($event)->confirmed(3, 2)->create();
        $originalRespondedAt = $guest->responded_at;

        $this->actingAs($user)
            ->patch(route('events.guests.update', [$event, $guest]), [
                'name' => $guest->name,
                'status' => GuestStatus::Declined->value,
                'adult_companions' => 3,
                'child_companions' => 2,
            ])
            ->assertRedirect();

        $guest->refresh();
        $this->assertSame(GuestStatus::Declined, $guest->status);
        $this->assertSame(0, $guest->adult_companions);
        $this->assertSame(0, $guest->child_companions);
        $this->assertTrue($originalRespondedAt->equalTo($guest->responded_at));

        $this->actingAs($user)
            ->patch(route('events.guests.update', [$event, $guest]), [
                'name' => $guest->name,
                'status' => GuestStatus::Pending->value,
                'adult_companions' => 2,
                'child_companions' => 1,
            ])
            ->assertRedirect();

        $guest->refresh();
        $this->assertSame(GuestStatus::Pending, $guest->status);
        $this->assertSame(0, $guest->adult_companions);
        $this->assertSame(0, $guest->child_companions);
        $this->assertNull($guest->responded_at);
    }

    public function test_guest_edits_preserve_existing_response_timestamp(): void
    {
        $respondedAt = now()->subDays(2)->startOfSecond();
        $user = User::factory()->create();
        $event = Event::factory()->for($user, 'owner')->create();
        $guest = Guest::factory()->for($event)->confirmed(1)->create([
            'responded_at' => $respondedAt,
        ]);

        $this->actingAs($user)
            ->patch(route('events.guests.update', [$event, $guest]), [
                'name' => 'Corrected Name',
                'status' => GuestStatus::Confirmed->value,
                'adult_companions' => 2,
                'child_companions' => 1,
            ])
            ->assertRedirect();

        $guest->refresh();

        $this->assertSame('Corrected Name', $guest->name);
        $this->assertSame(2, $guest->adult_companions);
        $this->assertSame(1, $guest->child_companions);
        $this->assertTrue($respondedAt->equalTo($guest->responded_at));
    }

    public function test_guest_count_edits_clear_stale_named_companions(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->for($user, 'owner')->create();
        $guest = Guest::factory()->for($event)->confirmed(1, 1)->create();

        GuestCompanion::factory()->for($guest)->create(['name' => 'Named Adult']);
        GuestCompanion::factory()->for($guest)->child()->create(['name' => 'Named Child']);

        $this->actingAs($user)
            ->patch(route('events.guests.update', [$event, $guest]), [
                'name' => $guest->name,
                'status' => GuestStatus::Confirmed->value,
                'adult_companions' => 2,
                'child_companions' => 0,
            ])
            ->assertRedirect();

        $guest->refresh();

        $this->assertSame(2, $guest->adult_companions);
        $this->assertSame(0, $guest->child_companions);
        $this->assertDatabaseCount('guest_companions', 0);
    }

    public function test_guest_name_edits_preserve_named_companions_when_counts_do_not_change(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->for($user, 'owner')->create();
        $guest = Guest::factory()->for($event)->confirmed(1, 0)->create();
        GuestCompanion::factory()->for($guest)->create(['name' => 'Named Adult']);

        $this->actingAs($user)
            ->patch(route('events.guests.update', [$event, $guest]), [
                'name' => 'Corrected Name',
                'status' => GuestStatus::Confirmed->value,
                'adult_companions' => 1,
                'child_companions' => 0,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('guest_companions', [
            'guest_id' => $guest->id,
            'name' => 'Named Adult',
        ]);
    }

    public function test_guest_list_returns_named_companions_without_full_list_on_regular_view(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->for($user, 'owner')->create();
        $guest = Guest::factory()->for($event)->confirmed(1, 1)->create(['name' => 'Alex Guest']);

        GuestCompanion::factory()->for($guest)->create(['name' => 'Adult Companion']);
        GuestCompanion::factory()->for($guest)->child()->create(['name' => 'Child Companion']);

        $this->actingAs($user)
            ->get(route('events.guests.index', $event))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('guests.data.0.name', 'Alex Guest')
                ->where('filters.view', null)
                ->has('guests.data.0.companions', 2)
                ->where('guests.data.0.companions.0.name', 'Adult Companion')
                ->where('guests.data.0.companions.0.is_child', false)
                ->where('guests.data.0.companions.1.name', 'Child Companion')
                ->where('guests.data.0.companions.1.is_child', true)
                ->has('fullGuestList', 0)
                ->where('guests.data.0.invitation_url', route('public.invitations.show', [$event, $guest->invitation_token]))
            );
    }

    public function test_full_guest_list_includes_named_and_count_only_companions(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->for($user, 'owner')->create();
        $alex = Guest::factory()->for($event)->confirmed(2, 1)->create(['name' => 'Alex Guest']);
        $zoe = Guest::factory()->for($event)->pending()->create(['name' => 'Zoe Guest']);

        GuestCompanion::factory()->for($alex)->create(['name' => 'Named Adult']);

        $this->actingAs($user)
            ->get(route('events.guests.index', ['event' => $event, 'view' => 'full']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.view', 'full')
                ->has('fullGuestList', 5)
                ->where('fullGuestList.0.name', 'Alex Guest')
                ->where('fullGuestList.0.primary_guest', 'Alex Guest')
                ->where('fullGuestList.0.is_child', false)
                ->where('fullGuestList.0.is_primary', true)
                ->where('fullGuestList.0.is_named', true)
                ->where('fullGuestList.1.name', 'Named Adult')
                ->where('fullGuestList.1.primary_guest', 'Alex Guest')
                ->where('fullGuestList.1.is_child', false)
                ->where('fullGuestList.1.is_primary', false)
                ->where('fullGuestList.1.is_named', true)
                ->where('fullGuestList.2.name', null)
                ->where('fullGuestList.2.primary_guest', 'Alex Guest')
                ->where('fullGuestList.2.is_child', false)
                ->where('fullGuestList.2.is_primary', false)
                ->where('fullGuestList.2.is_named', false)
                ->where('fullGuestList.3.name', null)
                ->where('fullGuestList.3.primary_guest', 'Alex Guest')
                ->where('fullGuestList.3.is_child', true)
                ->where('fullGuestList.3.is_primary', false)
                ->where('fullGuestList.3.is_named', false)
                ->where('fullGuestList.4.name', $zoe->name)
                ->where('fullGuestList.4.is_primary', true)
                ->where('fullGuestList.4.is_named', true)
                ->missing('fullGuestList.0.invitation_url')
                ->missing('fullGuestList.0.invitation_token')
            );
    }

    public function test_pagination_sorting_filters_and_invalid_filters(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->for($user, 'owner')->create();

        Guest::factory()->for($event)->confirmed()->create(['name' => 'Charlie']);
        Guest::factory()->for($event)->pending()->create(['name' => 'Bravo']);
        Guest::factory()->for($event)->declined()->create(['name' => 'Alpha']);
        Guest::factory()->count(20)->for($event)->pending()->sequence(
            ...array_map(fn (int $index): array => ['name' => sprintf('Guest %02d', $index)], range(1, 20)),
        )->create();

        $this->actingAs($user)
            ->get(route('events.guests.index', $event))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('guests.per_page', 20)
                ->where('guests.current_page', 1)
                ->where('guests.last_page', 2)
                ->where('guests.data.0.name', 'Alpha')
                ->has('fullGuestList', 0));

        $this->actingAs($user)
            ->get(route('events.guests.index', ['event' => $event, 'status' => GuestStatus::Confirmed->value]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.status', 'confirmed')
                ->where('filters.view', null)
                ->where('guests.total', 1)
                ->where('guests.data.0.name', 'Charlie')
                ->has('fullGuestList', 0));

        $this->actingAs($user)
            ->get(route('events.guests.index', ['event' => $event, 'view' => 'full']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.status', null)
                ->where('filters.view', 'full')
                ->where('guests.total', 23)
                ->where('guests.current_page', 1)
                ->where('guests.last_page', 2)
                ->has('fullGuestList', 20)
                ->where('fullGuestList.0.name', 'Alpha')
                ->where('fullGuestList.0.is_named', true)
                ->missing('fullGuestList.0.invitation_url')
                ->missing('fullGuestList.0.invitation_token'));

        $this->actingAs($user)
            ->get(route('events.guests.index', ['event' => $event, 'view' => 'full', 'page' => 2]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.view', 'full')
                ->where('guests.current_page', 2)
                ->has('fullGuestList', 3));

        $this->actingAs($user)
            ->get(route('events.guests.index', ['event' => $event, 'status' => 'maybe']))
            ->assertNotFound();

        $this->actingAs($user)
            ->get(route('events.guests.index', ['event' => $event, 'view' => 'modal']))
            ->assertNotFound();
    }

    public function test_invitation_tokens_are_unique_hidden_and_invalidated_on_delete(): void
    {
        $event = Event::factory()->create();
        $guests = Guest::factory()->count(5)->for($event)->create();

        $tokens = $guests->pluck('invitation_token');

        $this->assertSame(5, $tokens->unique()->count());
        $this->assertTrue($tokens->every(fn (string $token): bool => strlen($token) === 48));
        $this->assertTrue($tokens->every(fn (string $token): bool => ! ctype_digit($token)));
        $this->assertArrayNotHasKey('invitation_token', $guests->first()->toArray());
        $this->assertArrayNotHasKey('response_token_hash', $guests->first()->toArray());

        $guest = $guests->first();
        $url = route('public.invitations.show', [$event, $guest->invitation_token]);

        $response = $this->get($url)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('PublicEvent/Show')
                ->missing('guests')
                ->missing('guest')
                ->missing('event.guests')
                ->missing('event.owner')
                ->missing('event.user_id'));

        $this->assertStringContainsString('private', $response->headers->get('Cache-Control') ?? '');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control') ?? '');
        $this->assertStringNotContainsString('public', $response->headers->get('Cache-Control') ?? '');

        $guest->delete();

        $this->get($url)->assertNotFound();
    }

    public function test_guest_index_exposes_canonical_invitation_urls_without_internal_token_fields(): void
    {
        config()->set('app.url', 'https://events.example.com');

        $user = User::factory()->create();
        $event = Event::factory()->for($user, 'owner')->create();
        $guest = Guest::factory()->for($event)->create(['name' => 'Trusted Link Guest']);

        $this->actingAs($user)
            ->withHeader('Host', 'attacker.test')
            ->get(route('events.guests.index', $event))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('guests.data.0.invitation_url', 'https://events.example.com/e/'.$event->public_id.'/invitation/'.$guest->invitation_token)
                ->where('guests.data.0.invitation_url', fn (string $url): bool => ! str_contains($url, 'attacker.test'))
                ->missing('guests.data.0.invitation_token'));
    }

    public function test_guest_index_query_count_is_bounded_for_a_page(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->for($user, 'owner')->create();
        $guests = Guest::factory()->count(20)->for($event)->create();
        $guests->each(fn (Guest $guest): GuestCompanion => GuestCompanion::factory()->for($guest)->create());

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($user)
            ->get(route('events.guests.index', $event))
            ->assertOk();

        $this->assertLessThanOrEqual(7, count(DB::getQueryLog()));

        DB::flushQueryLog();

        $this->actingAs($user)
            ->get(route('events.guests.index', ['event' => $event, 'view' => 'full']))
            ->assertOk();

        $this->assertLessThanOrEqual(7, count(DB::getQueryLog()));

        DB::disableQueryLog();
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'name' => 'Valid Guest',
            'status' => GuestStatus::Pending->value,
            'adult_companions' => 0,
            'child_companions' => 0,
        ];
    }
}
