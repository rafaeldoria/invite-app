<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use App\Support\Events\EventCoverImages;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class EventManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Str::createUlidsNormally();
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_event_pages_require_a_verified_organizer(): void
    {
        $this->get(route('events.index'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->unverified()->create())
            ->get(route('events.index'))
            ->assertRedirect(route('verification.notice'));

        $this->actingAs(User::factory()->create())
            ->get(route('events.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Events/Index')
                ->has('events', 0));
    }

    public function test_verified_organizer_can_create_view_update_and_delete_an_event_without_cover(): void
    {
        Carbon::setTestNow('2029-01-01 12:00:00');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('events.store'), $this->validPayload([
                'starts_date' => '2029-02-10',
                'starts_time' => '20:30',
                'timezone' => 'America/Sao_Paulo',
            ]))
            ->assertRedirect();

        $event = Event::firstOrFail();

        $this->assertSame($user->id, $event->user_id);
        $this->assertSame('2029-02-10', $event->local_starts_date);
        $this->assertSame('20:30', $event->local_starts_time);
        $this->assertSame('2029-02-10 23:30:00', $event->starts_at->setTimezone('UTC')->format('Y-m-d H:i:s'));
        $this->assertNotSame($event->name, $event->public_id);

        $this->actingAs($user)
            ->get(route('events.show', $event))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Events/Show')
                ->where('event.public_id', $event->public_id)
                ->missing('event.id')
                ->missing('event.user_id')
                ->where('event.cover_image', null));

        $this->actingAs($user)
            ->patch(route('events.update', $event), $this->validPayload([
                'name' => 'Updated event',
                'theme' => 'Graduation',
                'starts_date' => '2029-02-11',
                'starts_time' => '09:15',
                'timezone' => 'UTC',
            ]))
            ->assertRedirect(route('events.show', $event));

        $event->refresh();
        $this->assertSame('Updated event', $event->name);
        $this->assertSame('Graduation', $event->theme);
        $this->assertSame('2029-02-11 09:15:00', $event->starts_at->setTimezone('UTC')->format('Y-m-d H:i:s'));

        $this->actingAs($user)
            ->delete(route('events.destroy', $event))
            ->assertRedirect(route('events.index'));

        $this->assertDatabaseMissing('events', ['public_id' => $event->public_id]);
    }

    public function test_cross_owner_access_is_forbidden(): void
    {
        $owner = User::factory()->create();
        $otherOrganizer = User::factory()->create();
        $event = Event::factory()->for($owner, 'owner')->create();

        $this->actingAs($otherOrganizer)
            ->get(route('events.show', $event))
            ->assertForbidden();

        $this->actingAs($otherOrganizer)
            ->patch(route('events.update', $event), $this->validPayload())
            ->assertForbidden();

        $this->actingAs($otherOrganizer)
            ->delete(route('events.destroy', $event))
            ->assertForbidden();
    }

    public function test_validation_rejects_invalid_fields_and_past_creation(): void
    {
        Carbon::setTestNow('2030-01-10 12:00:00');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('events.store'), [
                'name' => '',
                'description' => '',
                'starts_date' => '2030-02-31',
                'starts_time' => '25:00',
                'timezone' => 'Invalid/Zone',
                'location' => '',
                'theme' => str_repeat('a', 81),
                'cover_image' => UploadedFile::fake()->create('cover.txt', 10, 'text/plain'),
            ])
            ->assertSessionHasErrors(['name', 'description', 'starts_date', 'starts_time', 'timezone', 'location', 'theme', 'cover_image']);

        $this->actingAs($user)
            ->post(route('events.store'), $this->validPayload([
                'starts_date' => '2030-01-09',
                'starts_time' => '20:00',
                'timezone' => 'UTC',
            ]))
            ->assertSessionHasErrors(['starts_date']);

        $this->actingAs($user)
            ->post(route('events.store'), [
                ...$this->validPayload(),
                'cover_image' => UploadedFile::fake()->create('large.jpg', 5121, 'image/jpeg'),
            ])
            ->assertSessionHasErrors(['cover_image']);
    }

    public function test_past_event_can_be_edited_without_changing_its_existing_start(): void
    {
        Carbon::setTestNow('2030-01-10 12:00:00');
        $user = User::factory()->create();
        $event = Event::factory()->for($user, 'owner')->past()->create([
            'starts_at' => Carbon::parse('2030-01-01 18:00:00', 'UTC'),
            'timezone' => 'UTC',
        ]);

        $this->actingAs($user)
            ->patch(route('events.update', $event), $this->validPayload([
                'name' => 'Past event updated',
                'starts_date' => '2030-01-01',
                'starts_time' => '18:00',
                'timezone' => 'UTC',
            ]))
            ->assertRedirect(route('events.show', $event));

        $this->actingAs($user)
            ->patch(route('events.update', $event), $this->validPayload([
                'starts_date' => '2030-01-02',
                'starts_time' => '18:00',
                'timezone' => 'UTC',
            ]))
            ->assertSessionHasErrors(['starts_date']);
    }

    public function test_cover_image_upload_replace_remove_and_delete_cleanup(): void
    {
        Storage::fake('s3');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('events.store'), [
                ...$this->validPayload(),
                'cover_image' => $this->tinyPng('cover.png'),
            ])
            ->assertRedirect();

        $event = Event::firstOrFail();
        $oldKey = $event->cover_image_key;
        $this->assertNotNull($oldKey);
        Storage::disk('s3')->assertExists($oldKey);
        $this->assertSame('image/png', $event->cover_image_mime);
        $this->assertSame(1, $event->cover_image_width);

        $this->actingAs($user)
            ->patch(route('events.update', $event), [
                ...$this->validPayload(['name' => 'With replacement']),
                'cover_image' => $this->tinyPng('replacement.png'),
            ])
            ->assertRedirect(route('events.show', $event));

        $event->refresh();
        $newKey = $event->cover_image_key;
        $this->assertNotSame($oldKey, $newKey);
        Storage::disk('s3')->assertMissing($oldKey);
        Storage::disk('s3')->assertExists($newKey);

        $this->actingAs($user)
            ->patch(route('events.update', $event), [
                ...$this->validPayload(['name' => 'Without cover']),
                'remove_cover_image' => true,
            ])
            ->assertRedirect(route('events.show', $event));

        $event->refresh();
        $this->assertNull($event->cover_image_key);
        Storage::disk('s3')->assertMissing($newKey);

        Storage::disk('s3')->put('event-covers/delete-me.jpg', 'cover');
        $event->update([
            'cover_image_disk' => 's3',
            'cover_image_key' => 'event-covers/delete-me.jpg',
            'cover_image_mime' => 'image/jpeg',
            'cover_image_size' => 10,
        ]);

        $this->actingAs($user)
            ->delete(route('events.destroy', $event))
            ->assertRedirect(route('events.index'));

        Storage::disk('s3')->assertMissing('event-covers/delete-me.jpg');
    }

    public function test_failed_create_after_upload_cleans_new_cover(): void
    {
        Storage::fake('s3');
        $user = User::factory()->create();
        $publicId = '01J1EVENTFAILCLEANUP0000';
        Event::factory()->for($user, 'owner')->create(['public_id' => $publicId]);
        Str::createUlidsUsing(fn (): string => $publicId);

        $this->actingAs($user)
            ->post(route('events.store'), [
                ...$this->validPayload(['name' => 'Duplicate public id']),
                'cover_image' => $this->tinyPng('cover.png'),
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertCount(0, Storage::disk('s3')->allFiles('event-covers'));
    }

    public function test_storage_delete_failure_is_logged_without_blocking_data_change(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->with('Event cover image cleanup failed.', Mockery::on(fn (array $context): bool => $context['disk'] === 's3' && $context['key'] === 'event-covers/failing.jpg'));

        Storage::shouldReceive('disk')
            ->with('s3')
            ->andThrow(new RuntimeException('provider unavailable'));

        app(EventCoverImages::class)->delete('s3', 'event-covers/failing.jpg');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return [
            'name' => 'Launch dinner',
            'description' => "Dinner and welcome remarks.\nPlease arrive on time.",
            'starts_date' => '2031-05-20',
            'starts_time' => '19:00',
            'timezone' => 'America/Sao_Paulo',
            'location' => 'Main hall',
            'theme' => '',
            ...$overrides,
        ];
    }

    private function tinyPng(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII='
        ));
    }
}
