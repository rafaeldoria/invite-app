<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EventSharingTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_public_event_page_uses_opaque_id_without_authentication_and_allowlisted_props(): void
    {
        Storage::fake('s3');
        config()->set('app.url', 'https://invite.test');

        $event = Event::factory()->withCover()->create([
            'name' => 'Garden Party',
            'description' => "Dinner\nDancing",
            'location' => 'Main Hall',
            'share_message' => 'Organizer-only draft',
        ]);

        $response = $this->get(route('public.events.show', $event))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('PublicEvent/Show')
                ->where('event.name', 'Garden Party')
                ->where('event.description', "Dinner\nDancing")
                ->where('event.location', 'Main Hall')
                ->where('event.canonical_url', 'https://invite.test/e/'.$event->public_id)
                ->where('event.rsvp.available', false)
                ->where('event.rsvp.url', null)
                ->has('event.cover_image.url')
                ->where('event.cover_image.width', 1200)
                ->where('event.cover_image.height', 800)
                ->where('meta.url', 'https://invite.test/e/'.$event->public_id)
                ->missing('event.id')
                ->missing('event.public_id')
                ->missing('event.user_id')
                ->missing('event.owner')
                ->missing('event.guests')
                ->missing('event.share_message')
                ->missing('event.share')
                ->missing('event.cover_image.key'));

        $this->assertStringContainsString('public', $response->headers->get('Cache-Control') ?? '');
        $this->assertStringContainsString('max-age=60', $response->headers->get('Cache-Control') ?? '');
        $this->assertStringContainsString('stale-while-revalidate=300', $response->headers->get('Cache-Control') ?? '');
    }

    public function test_invalid_public_identifiers_return_not_found(): void
    {
        $this->get('/e/not-a-real-public-id')->assertNotFound();
    }

    public function test_public_metadata_is_plain_text_and_escaped(): void
    {
        $event = Event::factory()->create([
            'name' => '<strong>Launch</strong>',
            'description' => '<script>alert("x")</script> Dinner & dancing',
        ]);

        $this->get(route('public.events.show', $event))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('meta.title', 'Launch')
                ->where('meta.description', 'alert("x") Dinner & dancing'));
    }

    public function test_public_canonical_url_uses_trusted_app_url_not_request_host(): void
    {
        config()->set('app.url', 'https://events.example.com');
        $event = Event::factory()->create();

        $this->withHeader('Host', 'attacker.test')
            ->get(route('public.events.show', $event))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('event.canonical_url', 'https://events.example.com/e/'.$event->public_id)
                ->where('meta.url', 'https://events.example.com/e/'.$event->public_id));
    }

    public function test_owner_can_update_custom_share_message_and_whitespace_uses_default(): void
    {
        $owner = User::factory()->create();
        $event = Event::factory()->for($owner, 'owner')->create();

        $this->actingAs($owner)
            ->patch(route('events.share-message.update', $event), ['share_message' => 'A'])
            ->assertRedirect();

        $this->assertSame('A', $event->refresh()->share_message);

        $this->actingAs($owner)
            ->patch(route('events.share-message.update', $event), ['share_message' => str_repeat('x', 500)])
            ->assertRedirect();

        $this->assertSame(str_repeat('x', 500), $event->refresh()->share_message);

        $this->actingAs($owner)
            ->patch(route('events.share-message.update', $event), ['share_message' => " \n\t "])
            ->assertRedirect();

        $this->assertNull($event->refresh()->share_message);
    }

    public function test_share_message_update_authorization_and_validation_boundaries(): void
    {
        $owner = User::factory()->create();
        $otherOrganizer = User::factory()->create();
        $event = Event::factory()->for($owner, 'owner')->create();

        $this->patch(route('events.share-message.update', $event), ['share_message' => 'Guest edit'])
            ->assertRedirect(route('login'));

        $this->actingAs($otherOrganizer)
            ->patch(route('events.share-message.update', $event), ['share_message' => 'Cross owner edit'])
            ->assertForbidden();

        $this->actingAs($owner)
            ->patch(route('events.share-message.update', $event), ['share_message' => str_repeat('x', 501)])
            ->assertSessionHasErrors(['share_message']);
    }

    public function test_organizer_share_preview_uses_locale_and_includes_url_once(): void
    {
        Carbon::setTestNow('2030-01-01 12:00:00');
        config()->set('app.url', 'https://invite.test');

        $owner = User::factory()->create();
        $event = Event::factory()->for($owner, 'owner')->create([
            'name' => 'Festa São João',
            'starts_at' => Carbon::parse('2030-06-20 22:30:00', 'UTC'),
            'timezone' => 'America/Sao_Paulo',
            'location' => 'Salão & Jardim',
        ]);

        $this->actingAs($owner)
            ->withSession(['locale' => 'pt-BR'])
            ->get(route('events.show', $event))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('event.share.default_message', 'Você está convidado(a) para Festa São João.')
                ->where('event.share.canonical_url', 'https://invite.test/e/'.$event->public_id)
                ->where('event.share.update_url', route('events.share-message.update', $event))
                ->where('event.share.whatsapp_url', fn (string $url): bool => str_starts_with($url, 'https://wa.me/?text=')
                    && str_contains(rawurldecode(substr($url, strlen('https://wa.me/?text='))), 'Data e hora:'))
                ->where('event.share.final_message', fn (string $message): bool => str_contains($message, 'Data e hora:')
                    && str_contains($message, 'Salão & Jardim')
                    && substr_count($message, 'https://invite.test/e/'.$event->public_id) === 1));

        $this->actingAs($owner)
            ->withSession(['locale' => 'en-US'])
            ->get(route('events.show', $event))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('event.share.default_message', 'You are invited to Festa São João.')
                ->where('event.share.final_message', fn (string $message): bool => str_contains($message, 'Date and time:')
                    && substr_count($message, 'https://invite.test/e/'.$event->public_id) === 1));
    }
}
