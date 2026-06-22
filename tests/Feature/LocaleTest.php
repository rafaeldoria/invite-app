<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        App::setLocale(config('app.locale'));

        parent::tearDown();
    }

    public function test_default_locale_is_brazilian_portuguese(): void
    {
        $this->withHeader('Accept-Language', '')
            ->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('locale', 'pt-BR'));
    }

    public function test_supported_browser_preference_is_used_without_an_explicit_preference(): void
    {
        $this->withHeader('Accept-Language', 'en-US,en;q=0.9,pt-BR;q=0.8')
            ->get('/')
            ->assertInertia(fn (Assert $page) => $page->where('locale', 'en-US'));
    }

    public function test_explicit_locale_is_persisted_in_session_and_cookie(): void
    {
        $response = $this->from('/')->patch(route('locale.update'), ['locale' => 'en-US']);

        $response
            ->assertRedirect('/')
            ->assertCookie('locale', 'en-US');

        $this->get('/')
            ->assertInertia(fn (Assert $page) => $page->where('locale', 'en-US'));
    }

    public function test_explicit_preference_wins_for_authenticated_requests(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['locale' => 'pt-BR'])
            ->withHeader('Accept-Language', 'en-US')
            ->get('/')
            ->assertInertia(fn (Assert $page) => $page->where('locale', 'pt-BR'));
    }

    public function test_cookie_preference_survives_a_new_session(): void
    {
        $this->withCookie('locale', 'en-US')
            ->get('/')
            ->assertInertia(fn (Assert $page) => $page->where('locale', 'en-US'));
    }

    #[DataProvider('unsupportedLocales')]
    public function test_unsupported_locale_values_safely_fall_back_to_default(mixed $locale): void
    {
        $this->from('/')
            ->patch(route('locale.update'), ['locale' => $locale])
            ->assertRedirect('/');

        $this->get('/')
            ->assertInertia(fn (Assert $page) => $page->where('locale', 'pt-BR'));
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function unsupportedLocales(): array
    {
        return [
            'empty' => [''],
            'unsupported' => ['fr-FR'],
            'mixed case' => ['EN-us'],
            'path traversal' => ['../../en-US'],
            'array input' => [['en-US']],
        ];
    }

    public function test_validation_messages_follow_the_active_locale(): void
    {
        Route::middleware('web')->post('/locale-test/validation', function () {
            request()->validate(['name' => ['required']]);
        });

        $this->withSession(['locale' => 'pt-BR'])
            ->from('/')
            ->post('/locale-test/validation')
            ->assertSessionHasErrors(['name' => 'O campo name é obrigatório.']);

        $this->withSession(['locale' => 'en-US'])
            ->from('/')
            ->post('/locale-test/validation')
            ->assertSessionHasErrors(['name' => 'The name field is required.']);
    }

    public function test_authentication_and_mail_catalogs_follow_the_active_locale(): void
    {
        App::setLocale('pt-BR');
        $this->assertSame('As credenciais informadas não correspondem aos nossos registros.', __('auth.failed'));
        $this->assertSame('Redefinir senha', __('Reset Password'));
        $this->assertSame('Verificar endereço de e-mail', __('Verify Email Address'));

        App::setLocale('en-US');
        $this->assertSame('These credentials do not match our records.', __('auth.failed'));
        $this->assertSame('Reset Password', __('Reset Password'));
        $this->assertSame('Verify Email Address', __('Verify Email Address'));
    }

    public function test_validation_catalogs_cover_laravel_rules_and_have_matching_shapes(): void
    {
        $framework = require base_path('vendor/laravel/framework/src/Illuminate/Translation/lang/en/validation.php');
        $english = require lang_path('en-US/validation.php');
        $portuguese = require lang_path('pt-BR/validation.php');

        $this->assertEmpty(array_diff(array_keys($framework), array_keys($english)));
        $this->assertSame(array_keys(Arr::dot($english)), array_keys(Arr::dot($portuguese)));
    }

    public function test_locale_preference_is_rate_limited_with_a_localized_message(): void
    {
        $this->withSession(['locale' => 'pt-BR']);

        for ($attempt = 1; $attempt <= 20; $attempt++) {
            $this->patch(route('locale.update'), ['locale' => 'pt-BR'])->assertRedirect();
        }

        $this->patch(route('locale.update'), ['locale' => 'pt-BR'])
            ->assertTooManyRequests()
            ->assertSee('Muitas tentativas. Aguarde um momento e tente novamente.');
    }

    public function test_inertia_locale_preference_throttle_renders_the_localized_error_page(): void
    {
        for ($attempt = 1; $attempt <= 20; $attempt++) {
            $this->withHeader('X-Inertia', 'true')
                ->patch(route('locale.update'), ['locale' => 'en-US'])
                ->assertRedirect();
        }

        $this->withHeader('X-Inertia', 'true')
            ->patch(route('locale.update'), ['locale' => 'en-US'])
            ->assertTooManyRequests()
            ->assertHeader('X-Inertia', 'true')
            ->assertJsonPath('component', 'Error')
            ->assertJsonPath('props.status', 429);
    }
}
