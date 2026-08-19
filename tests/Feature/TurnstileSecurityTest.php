<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TurnstileSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.turnstile.site_key', '1x00000000000000000000AA');
        config()->set('services.turnstile.secret_key', '1x0000000000000000000000000000000AA');
        config()->set('services.turnstile.hostname', 'toko.example.test');
    }

    public function test_login_and_registration_render_turnstile_widgets_with_distinct_actions(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('challenges.cloudflare.com/turnstile/v0/api.js', false)
            ->assertSee('data-action="login"', false);

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('data-action="register"', false);

        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('data-action="recovery"', false);
    }

    public function test_login_fails_closed_when_turnstile_token_is_missing(): void
    {
        $buyer = User::factory()->create(['password' => 'password123']);

        $this->from(route('login'))->post(route('login.store'), [
            'email' => $buyer->email,
            'password' => 'password123',
        ])->assertRedirect(route('login'))
            ->assertSessionHasErrors('cf-turnstile-response');

        $this->assertGuest();
        Http::assertNothingSent();
    }

    public function test_registration_fails_closed_when_turnstile_token_is_missing(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Pembeli Baru',
            'email' => 'baru@example.test',
            'phone' => '081288888888',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('cf-turnstile-response');

        $this->assertDatabaseMissing('users', ['email' => 'baru@example.test']);
        Http::assertNothingSent();
    }

    public function test_login_validates_turnstile_server_side_with_action_and_hostname(): void
    {
        $buyer = User::factory()->create(['password' => 'password123']);

        Http::fake([
            'challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
                'success' => true,
                'action' => 'login',
                'hostname' => 'toko.example.test',
            ]),
        ]);

        $this->post(route('login.store'), [
            'email' => $buyer->email,
            'password' => 'password123',
            'cf-turnstile-response' => 'verified-test-token',
        ])->assertRedirect(route('buyer.dashboard'));

        $this->assertAuthenticatedAs($buyer);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://challenges.cloudflare.com/turnstile/v0/siteverify'
            && $request['secret'] === '1x0000000000000000000000000000000AA'
            && $request['response'] === 'verified-test-token');
    }

    public function test_turnstile_rejects_a_valid_token_for_the_wrong_action(): void
    {
        $buyer = User::factory()->create(['password' => 'password123']);

        Http::fake([
            'challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
                'success' => true,
                'action' => 'register',
                'hostname' => 'toko.example.test',
            ]),
        ]);

        $this->post(route('login.store'), [
            'email' => $buyer->email,
            'password' => 'password123',
            'cf-turnstile-response' => 'token-for-another-action',
        ])->assertSessionHasErrors('cf-turnstile-response');

        $this->assertGuest();
    }
}
