<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Event;
use App\Models\Guest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Inertia\Support\Header;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_direct_invalid_public_urls_render_localized_error_pages_without_sensitive_props(): void
    {
        $this->useProductionErrorRendering();

        $event = Event::factory()->create([
            'name' => 'Private Launch Reception QA',
            'description' => 'Confidential reception details',
            'location' => 'Hidden Ballroom QA',
        ]);
        $guest = Guest::factory()
            ->for($event)
            ->create([
                'name' => 'Confidential Guest QA',
                'response_token_hash' => hash('sha256', 'valid-management-token-qa'),
            ]);

        $paths = [
            '/e/invalid-event-public-id',
            route('public.invitations.show', [$event, 'invalid-invitation-token-qa']),
            route('public.invitations.rsvp.edit', [$event, 'invalid-invitation-token-qa']),
            route('public.rsvp.show', [$event, 'invalid-management-token-qa']),
        ];

        foreach ($paths as $path) {
            $response = $this->withSession(['locale' => 'pt-BR'])->get($path);

            $response
                ->assertNotFound()
                ->assertHeaderMissing('X-Inertia')
                ->assertInertia(fn (Assert $page) => $page
                    ->component('Error')
                    ->where('status', 404)
                    ->where('locale', 'pt-BR')
                    ->missing('event')
                    ->missing('guest')
                    ->missing('rsvp'));

            $this->assertSecurityHeaders($response);
            $this->assertStringNotContainsString($event->name, $response->getContent());
            $this->assertStringNotContainsString($event->description, $response->getContent());
            $this->assertStringNotContainsString($event->location, $response->getContent());
            $this->assertStringNotContainsString($guest->name, $response->getContent());
            $this->assertStringNotContainsString($guest->invitation_token, $response->getContent());
            $this->assertStringNotContainsString($guest->response_token_hash, $response->getContent());
        }
    }

    public function test_direct_web_error_pages_use_english_locale_when_selected(): void
    {
        $this->useProductionErrorRendering();

        $this->withSession(['locale' => 'en-US'])
            ->get('/e/invalid-event-public-id')
            ->assertNotFound()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Error')
                ->where('status', 404)
                ->where('locale', 'en-US'));
    }

    public function test_inertia_navigation_errors_render_the_same_error_page(): void
    {
        $this->useProductionErrorRendering();

        $event = Event::factory()->create();
        $assetVersion = app(HandleInertiaRequests::class)->version(request());

        $this->withSession(['locale' => 'en-US'])
            ->withHeaders([
                Header::INERTIA => 'true',
                Header::VERSION => $assetVersion,
            ])
            ->get(route('public.invitations.rsvp.edit', [$event, 'invalid-invitation-token-qa']))
            ->assertNotFound()
            ->assertHeader('X-Inertia', 'true')
            ->assertJsonPath('component', 'Error')
            ->assertJsonPath('props.status', 404)
            ->assertJsonPath('props.locale', 'en-US')
            ->assertJsonMissingPath('props.event')
            ->assertJsonMissingPath('props.guest')
            ->assertJsonMissingPath('props.rsvp');
    }

    public function test_api_error_requests_keep_json_error_behavior(): void
    {
        $this->useProductionErrorRendering();

        $this->getJson('/api/missing-resource')
            ->assertNotFound()
            ->assertHeaderMissing('X-Inertia')
            ->assertJsonStructure(['message']);
    }

    public function test_direct_debug_server_errors_keep_laravels_debug_response(): void
    {
        config()->set('app.debug', true);

        Route::get('/error-page/debug-server-error', fn () => throw new \RuntimeException('Debug response check'));

        $this->get('/error-page/debug-server-error')
            ->assertInternalServerError()
            ->assertHeaderMissing('X-Inertia')
            ->assertSee('Debug response check')
            ->assertDontSee('"component":"Error"', false);
    }

    private function useProductionErrorRendering(): void
    {
        config()->set('app.debug', false);
        $this->app->instance('env', 'production');
    }

    private function assertSecurityHeaders(TestResponse $response): void
    {
        $response
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()')
            ->assertHeader('Content-Security-Policy');
    }
}
