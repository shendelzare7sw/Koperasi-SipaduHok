<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordAndPublicInformationTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_can_request_password_reset_link(): void
    {
        Notification::fake();
        $buyer = User::factory()->create();

        $this->post(route('password.email'), ['email' => $buyer->email])->assertRedirect();
        Notification::assertSentTo($buyer, ResetPassword::class);
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
