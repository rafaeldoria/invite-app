<?php

namespace Tests\Feature;

use App\Enums\GuestStatus;
use App\Models\Event;
use App\Models\Guest;
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
                ->where('guests.data.0.status', 'pending')
                ->where('guests.data.0.companion_count', 0)
                ->where('guests.data.0.invitation_url', route('public.invitations.show', [$event, $guest->invitation_token]))
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
            ->assertForbidden();

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
                'child_companions' => 21,
            ])
            ->assertSessionHasErrors(['status', 'adult_companions', 'child_companions']);
    }

    public function test_status_count_invariants_match_rsvp_contract(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->for($user, 'owner')->create();
        $guest = Guest::factory()->for($event)->confirmed(3, 2)->create();

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
                ->where('guests.data.0.name', 'Alpha'));

        $this->actingAs($user)
            ->get(route('events.guests.index', ['event' => $event, 'status' => GuestStatus::Confirmed->value]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.status', 'confirmed')
                ->where('guests.total', 1)
                ->where('guests.data.0.name', 'Charlie'));

        $this->actingAs($user)
            ->get(route('events.guests.index', ['event' => $event, 'status' => 'maybe']))
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

        $this->get($url)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('PublicEvent/Show')
                ->missing('guests')
                ->missing('guest')
                ->missing('event.guests')
                ->missing('event.owner')
                ->missing('event.user_id'));

        $guest->delete();

        $this->get($url)->assertNotFound();
    }

    public function test_guest_index_query_count_is_bounded_for_a_page(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->for($user, 'owner')->create();
        Guest::factory()->count(20)->for($event)->create();

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($user)
            ->get(route('events.guests.index', $event))
            ->assertOk();

        $this->assertLessThanOrEqual(5, count(DB::getQueryLog()));

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
