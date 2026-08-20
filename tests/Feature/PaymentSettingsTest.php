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

    public function test_admin_can_store_encrypted_midtrans_credentials_from_panel(): void
    {
        config()->set('services.midtrans.server_key', null);
        config()->set('services.midtrans.client_key', null);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put(route('admin.settings.payment.update'), [
            'is_active' => '1',
            'environment' => 'sandbox',
            'server_key' => 'Mid-server-panel-secret',
            'client_key' => 'Mid-client-panel-key',
            'merchant_id' => 'G123456789',
            'current_password' => 'password',
        ])->assertRedirect();

        $setting = PaymentSetting::firstOrFail();
        $raw = DB::table('payment_settings')->first();
        $this->assertSame('Mid-server-panel-secret', $setting->server_key);
        $this->assertSame('Mid-client-panel-key', $setting->client_key);
        $this->assertNotSame('Mid-server-panel-secret', $raw->server_key);
        $this->assertNotSame('Mid-client-panel-key', $raw->client_key);
        $this->assertSame($admin->id, $setting->updated_by);

        $configuration = app(PaymentConfiguration::class);
        $this->assertTrue($configuration->isReady());
        $this->assertSame('G123456789', $configuration->merchantId());

        $this->get(route('admin.settings.payment.edit'))
            ->assertOk()
            ->assertSee('Siap menerima pembayaran')
            ->assertSee(route('payments.midtrans.notification'))
            ->assertDontSee('Mid-server-panel-secret')
            ->assertDontSee('Mid-client-panel-key');
    }

    public function test_payment_panel_requires_admin_password(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put(route('admin.settings.payment.update'), [
            'is_active' => '1',
            'environment' => 'production',
            'server_key' => 'SB-Mid-server-wrong-environment',
            'client_key' => 'SB-Mid-client-wrong-environment',
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
