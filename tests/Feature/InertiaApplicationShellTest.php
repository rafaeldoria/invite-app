<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InertiaApplicationShellTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_shared_props_match_the_application_contract(): void
    {
        $this->withHeader('Accept-Language', '')
            ->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Welcome')
                ->where('app.name', config('app.name'))
                ->where('auth.user', null)
                ->where('flash.success', null)
                ->where('flash.error', null)
                ->where('locale', 'pt-BR'));
    }

    public function test_authenticated_shared_props_only_expose_allowlisted_user_fields(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.user.id', $user->id)
                ->where('auth.user.name', $user->name)
                ->where('auth.user.email', $user->email)
                ->where('auth.user.email_verified_at', $user->email_verified_at?->toJSON())
                ->missing('auth.user.password')
                ->missing('auth.user.remember_token'));
    }

    public function test_flash_messages_are_consumed_after_one_inertia_response(): void
    {
        Route::get('/shell-test/flash', function () {
            return redirect()->route('home')
                ->with('success', 'Event saved.')
                ->with('error', 'Review the highlighted fields.');
        });

        $this->get('/shell-test/flash')
            ->assertRedirect(route('home'));

        $this->get('/')
            ->assertInertia(fn (Assert $page) => $page
                ->where('flash.success', 'Event saved.')
                ->where('flash.error', 'Review the highlighted fields.'));

        $this->get('/')
            ->assertInertia(fn (Assert $page) => $page
                ->where('flash.success', null)
                ->where('flash.error', null));
    }

    public function test_production_inertia_errors_render_the_error_page_with_the_original_status(): void
    {
        config()->set('app.debug', false);
        $this->app->instance('env', 'production');

        foreach ([403, 404, 419, 429, 500, 503] as $status) {
            Route::get("/shell-test/error-{$status}", fn () => abort($status));

            $this->withHeader('X-Inertia', 'true')
                ->get("/shell-test/error-{$status}")
                ->assertStatus($status)
                ->assertHeader('X-Inertia', 'true')
                ->assertJsonPath('component', 'Error')
                ->assertJsonPath('props.status', $status);
        }
    }

    public function test_non_inertia_errors_keep_laravels_standard_response(): void
    {
        config()->set('app.debug', false);
        $this->app->instance('env', 'production');

        Route::get('/shell-test/forbidden', fn () => abort(403));

        $this->get('/shell-test/forbidden')
            ->assertForbidden()
            ->assertHeaderMissing('X-Inertia');

        $this->get('/shell-test/missing')
            ->assertNotFound()
            ->assertHeaderMissing('X-Inertia');
    }
}
