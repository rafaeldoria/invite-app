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

class EventDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_requires_a_verified_owner(): void
    {
        $owner = User::factory()->create();
        $event = Event::factory()->for($owner, 'owner')->create();
        $otherOrganizer = User::factory()->create();

        $this->get(route('events.dashboard', $event))
            ->assertRedirect(route('login'));

        $this->actingAs(User::factory()->unverified()->create())
            ->get(route('events.dashboard', $event))
            ->assertRedirect(route('verification.notice'));

        $this->actingAs($otherOrganizer)
            ->get(route('events.dashboard', $event))
            ->assertForbidden()
            ->assertDontSee('metrics');

        $this->actingAs($owner)
            ->get(route('events.dashboard', $event))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Events/Dashboard')
                ->where('event.name', $event->name)
                ->where('metrics.total', 0));
    }

    public function test_empty_dashboard_returns_zero_integer_metrics(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->for($user, 'owner')->create();

        $this->assertDashboardMetrics($user, $event, [
            'total' => 0,
            'confirmed' => 0,
            'declined' => 0,
            'pending' => 0,
            'expected_attendees' => 0,
        ]);
    }

    public function test_dashboard_counts_pending_only_dataset(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->for($user, 'owner')->create();

        Guest::factory()->count(6)->for($event)->pending()->create();

        $this->assertDashboardMetrics($user, $event, [
            'total' => 6,
            'confirmed' => 0,
            'declined' => 0,
            'pending' => 6,
            'expected_attendees' => 0,
        ]);
    }

    public function test_dashboard_counts_one_guest_per_status(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->for($user, 'owner')->create();

        Guest::factory()->for($event)->pending()->create();
        Guest::factory()->for($event)->confirmed()->create();
        Guest::factory()->for($event)->declined()->create();

        $this->assertDashboardMetrics($user, $event, [
            'total' => 3,
            'confirmed' => 1,
            'declined' => 1,
            'pending' => 1,
            'expected_attendees' => 1,
        ]);
    }

    public function test_dashboard_counts_mixed_statuses_and_expected_attendees(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->for($user, 'owner')->create();
        $otherEvent = Event::factory()->create();

        Guest::factory()->count(4)->for($event)->pending()->create();
        Guest::factory()->count(3)->for($event)->declined()->create();
        Guest::factory()->for($event)->confirmed(2, 1)->create();
        Guest::factory()->for($event)->confirmed(0, 2)->create();
        Guest::factory()->count(5)->for($otherEvent)->confirmed(3, 3)->create();

        $this->assertDashboardMetrics($user, $event, [
            'total' => 9,
            'confirmed' => 2,
            'declined' => 3,
            'pending' => 4,
            'expected_attendees' => 7,
        ]);

        $this->actingAs($user)
            ->get(route('events.dashboard', $event))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('metrics.total', 9)
                ->where('metrics.confirmed', 2)
                ->where('metrics.declined', 3)
                ->where('metrics.pending', 4)
                ->where('metrics.expected_attendees', 7)
                ->where('links.guests.all', route('events.guests.index', $event))
                ->where('links.guests.confirmed', route('events.guests.index', [
                    'event' => $event,
                    'status' => GuestStatus::Confirmed->value,
                ]))
                ->where('links.guests.declined', route('events.guests.index', [
                    'event' => $event,
                    'status' => GuestStatus::Declined->value,
                ]))
                ->where('links.guests.pending', route('events.guests.index', [
                    'event' => $event,
                    'status' => GuestStatus::Pending->value,
                ])));
    }

    public function test_dashboard_returns_full_guest_list_with_named_companions(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->for($user, 'owner')->create();
        $otherEvent = Event::factory()->create();

        $alex = Guest::factory()->for($event)->confirmed(1, 1)->create(['name' => 'Alex Guest']);
        $zoe = Guest::factory()->for($event)->pending()->create(['name' => 'Zoe Guest']);
        $otherGuest = Guest::factory()->for($otherEvent)->confirmed(1)->create(['name' => 'Other Guest']);

        GuestCompanion::factory()->for($alex)->create(['name' => 'Adult Companion']);
        GuestCompanion::factory()->for($alex)->child()->create(['name' => 'Child Companion']);
        GuestCompanion::factory()->for($otherGuest)->create(['name' => 'Hidden Companion']);

        $this->actingAs($user)
            ->get(route('events.dashboard', $event))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('fullGuestList', 4)
                ->where('fullGuestList.0.name', 'Alex Guest')
                ->where('fullGuestList.0.primary_guest', 'Alex Guest')
                ->where('fullGuestList.0.is_child', false)
                ->where('fullGuestList.0.is_primary', true)
                ->where('fullGuestList.1.name', 'Adult Companion')
                ->where('fullGuestList.1.primary_guest', 'Alex Guest')
                ->where('fullGuestList.1.is_child', false)
                ->where('fullGuestList.1.is_primary', false)
                ->where('fullGuestList.2.name', 'Child Companion')
                ->where('fullGuestList.2.primary_guest', 'Alex Guest')
                ->where('fullGuestList.2.is_child', true)
                ->where('fullGuestList.2.is_primary', false)
                ->where('fullGuestList.3.name', $zoe->name)
                ->missing('fullGuestList.4')
            );
    }

    public function test_public_event_and_rsvp_pages_do_not_receive_dashboard_metrics(): void
    {
        $event = Event::factory()->create();

        $this->get(route('public.events.show', $event))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->missing('metrics')
                ->missing('links.guests'));

        $this->get(route('public.rsvp.create', $event))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->missing('metrics')
                ->missing('links.guests'));
    }

    public function test_dashboard_refreshes_after_rsvp_and_guest_deletion(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->for($user, 'owner')->create();
        $guest = Guest::factory()->for($event)->pending()->create(['name' => 'Pending Guest']);
        $declinedGuest = Guest::factory()->for($event)->declined()->create(['name' => 'Declined Guest']);

        $this->actingAs($user)
            ->get(route('events.dashboard', $event))
            ->assertInertia(fn (Assert $page) => $page
                ->where('metrics.total', 2)
                ->where('metrics.confirmed', 0)
                ->where('metrics.declined', 1)
                ->where('metrics.pending', 1));

        $this->patch(route('public.invitations.rsvp.update', [$event, $guest->invitation_token]), [
            'attendance' => GuestStatus::Confirmed->value,
            'adult_companions' => 1,
            'child_companions' => 1,
        ])->assertRedirect(route('public.invitations.rsvp.edit', [$event, $guest->invitation_token]));

        $this->actingAs($user)
            ->get(route('events.dashboard', $event))
            ->assertInertia(fn (Assert $page) => $page
                ->where('metrics.total', 2)
                ->where('metrics.confirmed', 1)
                ->where('metrics.declined', 1)
                ->where('metrics.pending', 0)
                ->where('metrics.expected_attendees', 3));

        $this->delete(route('events.guests.destroy', [$event, $declinedGuest]))
            ->assertRedirect();

        $this->get(route('events.dashboard', $event))
            ->assertInertia(fn (Assert $page) => $page
                ->where('metrics.total', 1)
                ->where('metrics.confirmed', 1)
                ->where('metrics.declined', 0)
                ->where('metrics.pending', 0)
                ->where('metrics.expected_attendees', 3));
    }

    public function test_each_dashboard_card_link_opens_the_matching_guest_filter(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->for($user, 'owner')->create();

        Guest::factory()->for($event)->pending()->create(['name' => 'Pending Guest']);
        Guest::factory()->for($event)->confirmed()->create(['name' => 'Confirmed Guest']);
        Guest::factory()->for($event)->declined()->create(['name' => 'Declined Guest']);

        $this->actingAs($user)
            ->get(route('events.guests.index', $event))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.status', null)
                ->where('guests.total', 3));

        foreach (GuestStatus::cases() as $status) {
            $this->actingAs($user)
                ->get(route('events.guests.index', [
                    'event' => $event,
                    'status' => $status->value,
                ]))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->where('filters.status', $status->value)
                    ->where('guests.total', 1));
        }
    }

    public function test_dashboard_query_count_is_constant_as_guest_volume_grows(): void
    {
        $user = User::factory()->create();
        $smallEvent = Event::factory()->for($user, 'owner')->create();
        $largeEvent = Event::factory()->for($user, 'owner')->create();

        Guest::factory()->count(3)->for($smallEvent)->pending()->create();
        Guest::factory()->count(250)->for($largeEvent)->confirmed(1, 1)->create();

        $smallQueryCount = $this->dashboardQueryCount($user, $smallEvent);
        $largeQueryCount = $this->dashboardQueryCount($user, $largeEvent);

        $this->assertSame($smallQueryCount, $largeQueryCount);
        $this->assertLessThanOrEqual(6, $largeQueryCount);
    }

    private function dashboardQueryCount(User $user, Event $event): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($user)
            ->get(route('events.dashboard', $event))
            ->assertOk();

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $queryCount;
    }

    /**
     * @param  array{total: int, confirmed: int, declined: int, pending: int, expected_attendees: int}  $metrics
     */
    private function assertDashboardMetrics(User $user, Event $event, array $metrics): void
    {
        $this->assertSame(
            $metrics['total'],
            $metrics['confirmed'] + $metrics['declined'] + $metrics['pending'],
        );

        $this->actingAs($user)
            ->get(route('events.dashboard', $event))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Events/Dashboard')
                ->where('metrics.total', $metrics['total'])
                ->where('metrics.confirmed', $metrics['confirmed'])
                ->where('metrics.declined', $metrics['declined'])
                ->where('metrics.pending', $metrics['pending'])
                ->where('metrics.expected_attendees', $metrics['expected_attendees']));
    }
}
