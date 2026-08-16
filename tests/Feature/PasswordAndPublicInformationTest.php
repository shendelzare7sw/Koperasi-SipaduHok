<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\AccountRecoveryOtpNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordAndPublicInformationTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_can_recover_account_by_phone_and_reset_password_after_otp(): void
    {
        Notification::fake();
        $otp = null;
        $buyer = User::factory()->create([
            'email' => 'pembeli@example.test',
            'phone' => '081234567899',
            'password' => 'password123',
        ]);

        $this->post(route('password.email'), ['identifier' => $buyer->phone])
            ->assertRedirect(route('recovery.otp.notice'));

        Notification::assertSentOnDemand(
            AccountRecoveryOtpNotification::class,
            function (AccountRecoveryOtpNotification $notification, array $channels, AnonymousNotifiable $notifiable) use (&$otp, $buyer): bool {
                $otp = $notification->code;

                return $channels === ['mail']
                    && array_key_exists($buyer->email, $notifiable->routes['mail']);
            }
        );

        $this->post(route('recovery.otp.verify'), ['code' => $otp])
            ->assertRedirect(route('recovery.password.edit'));

        $this->get(route('recovery.password.edit'))
            ->assertOk()
            ->assertSee($buyer->email);

        $this->post(route('password.update'), [
            'password' => 'kataSandi456',
            'password_confirmation' => 'kataSandi456',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('kataSandi456', $buyer->fresh()->password));
    }

    public function test_unknown_account_uses_the_same_recovery_response_without_sending_email(): void
    {
        Notification::fake();

        $this->post(route('password.email'), ['identifier' => 'tidak-ada@example.test'])
            ->assertRedirect(route('recovery.otp.notice'))
            ->assertSessionHas('success', 'Jika data cocok, kode OTP pemulihan telah dikirim ke email akun terdaftar.');

        $this->get(route('recovery.otp.notice'))
            ->assertOk()
            ->assertSee('email akun terdaftar');

        Notification::assertNothingSent();
    }

    public function test_public_information_and_legal_pages_are_available(): void
    {
        $pages = [
            'pages.about' => 'Tentang Koperasi',
            'pages.help' => 'Pusat Bantuan',
            'pages.payment' => 'Cara Pembayaran',
            'pages.shipping' => 'Kebijakan Pengiriman',
            'pages.returns' => 'Kebijakan Pembatalan & Pengembalian',
            'pages.privacy' => 'Kebijakan Privasi',
            'pages.terms' => 'Syarat & Ketentuan',
        ];

        foreach ($pages as $route => $heading) {
            $this->get(route($route))->assertOk()->assertSee($heading);
        }
    }

    public function test_admin_can_update_public_store_identity(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put(route('admin.settings.store.update'), [
            'legal_name' => 'Koperasi Sekolah Sipaduhok',
            'support_email' => 'support@example.test',
            'phone' => '021000000',
            'whatsapp' => '081200000000',
            'address' => 'Kompleks Sekolah Sipaduhok',
            'operating_hours' => 'Senin sampai Jumat',
            'description' => 'Toko resmi kebutuhan sekolah.',
        ])->assertRedirect();

        $this->assertDatabaseHas('store_settings', [
            'key' => 'legal_name',
            'value' => 'Koperasi Sekolah Sipaduhok',
        ]);
        $this->get(route('pages.about'))->assertSee('Koperasi Sekolah Sipaduhok');
    }

    public function test_application_favicon_assets_exist(): void
    {
        $this->assertFileExists(public_path('favicon.ico'));
        $this->assertGreaterThan(0, filesize(public_path('favicon.ico')));
        $this->get(route('catalog.index'))->assertSee('favicon-32x32.png', false);
    }
}
