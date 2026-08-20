<?php

namespace Tests\Feature;

use App\Models\PaymentSetting;
use App\Models\User;
use App\Services\Payments\PaymentConfiguration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PaymentSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_store_encrypted_paywuz_credentials_from_panel(): void
    {
        config()->set('services.paywuz.sandbox_api_key', null);
        config()->set('services.paywuz.production_api_key', null);
        $admin = User::factory()->admin()->create();
        $sandboxKey = 'pk_sand_'.str_repeat('a', 32);
        $productionKey = 'pk_live_'.str_repeat('b', 32);

        $this->actingAs($admin)->put(route('admin.settings.payment.update'), [
            'is_active' => '1',
            'environment' => 'sandbox',
            'sandbox_api_key' => $sandboxKey,
            'production_api_key' => $productionKey,
            'current_password' => 'password',
        ])->assertRedirect();

        $setting = PaymentSetting::firstOrFail();
        $raw = DB::table('payment_settings')->first();
        $this->assertSame('paywuz', $setting->provider);
        $this->assertSame($sandboxKey, $setting->sandbox_api_key);
        $this->assertSame($productionKey, $setting->production_api_key);
        $this->assertNotSame($sandboxKey, $raw->sandbox_api_key);
        $this->assertNotSame($productionKey, $raw->production_api_key);
        $this->assertSame($admin->id, $setting->updated_by);

        $configuration = app(PaymentConfiguration::class);
        $this->assertTrue($configuration->isReady());
        $this->assertSame($sandboxKey, $configuration->apiKey());

        $this->get(route('admin.settings.payment.edit'))
            ->assertOk()
            ->assertSee('Paywuz Payment Gateway')
            ->assertSee('Siap menerima pembayaran')
            ->assertSee(route('payments.paywuz.webhook'))
            ->assertDontSee($sandboxKey)
            ->assertDontSee($productionKey);
    }

    public function test_active_environment_requires_matching_paywuz_key(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put(route('admin.settings.payment.update'), [
            'is_active' => '1',
            'environment' => 'production',
            'production_api_key' => 'pk_sand_'.str_repeat('a', 32),
            'current_password' => 'password',
        ])->assertSessionHasErrors(['production_api_key']);

        $this->assertDatabaseCount('payment_settings', 0);
    }

    public function test_payment_panel_requires_admin_password(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put(route('admin.settings.payment.update'), [
            'is_active' => '1',
            'environment' => 'sandbox',
            'sandbox_api_key' => 'pk_sand_'.str_repeat('a', 32),
            'current_password' => 'wrong-password',
        ])->assertSessionHasErrors(['current_password']);

        $this->assertDatabaseCount('payment_settings', 0);
    }

    public function test_buyer_cannot_open_or_change_payment_settings(): void
    {
        $buyer = User::factory()->create();

        $this->actingAs($buyer)->get(route('admin.settings.payment.edit'))->assertForbidden();
        $this->put(route('admin.settings.payment.update'), [
            'is_active' => '0',
            'environment' => 'sandbox',
            'current_password' => 'password',
        ])->assertForbidden();
    }
}
