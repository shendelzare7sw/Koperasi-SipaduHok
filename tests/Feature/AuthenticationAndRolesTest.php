<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\RegistrationOtpNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuthenticationAndRolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_authentication_pages_use_one_buyer_profile_and_icon_password_controls(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('buyer_type', false)
            ->assertSee('fa-eye', false);

        $this->get(route('register'))
            ->assertOk()
            ->assertDontSee('buyer_type', false)
            ->assertDontSee('Tipe pembeli')
            ->assertSee('fa-eye', false)
            ->assertSee('data-confirm=', false);

        $this->assertFalse(Schema::hasColumn('users', 'buyer_type'));
    }

    public function test_public_registration_creates_buyer_only_after_email_otp_is_verified(): void
    {
        Notification::fake();
        $otp = null;

        $response = $this->post(route('register.store'), [
            'name' => 'Wali Siswa',
            'email' => 'wali@example.test',
            'phone' => '081234567890',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('register.otp.notice'));
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'wali@example.test']);

        Notification::assertSentOnDemand(
            RegistrationOtpNotification::class,
            function (RegistrationOtpNotification $notification, array $channels, AnonymousNotifiable $notifiable) use (&$otp): bool {
                $otp = $notification->code;

                return $channels === ['mail']
                    && array_key_exists('wali@example.test', $notifiable->routes['mail']);
            }
        );

        $this->post(route('register.otp.verify'), ['code' => $otp])
            ->assertRedirect(route('buyer.dashboard'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'wali@example.test',
            'role' => UserRole::Buyer->value,
        ]);
        $this->assertNotNull(User::where('email', 'wali@example.test')->value('email_verified_at'));
    }

    public function test_registration_otp_is_invalidated_after_five_wrong_attempts(): void
    {
        Notification::fake();
        $otp = null;

        $this->post(route('register.store'), [
            'name' => 'Pembeli Baru',
            'email' => 'baru@example.test',
            'phone' => '081299999999',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('register.otp.notice'));

        Notification::assertSentOnDemand(
            RegistrationOtpNotification::class,
            function (RegistrationOtpNotification $notification) use (&$otp): bool {
                $otp = $notification->code;

                return true;
            }
        );

        $wrongOtp = $otp === '000000' ? '000001' : '000000';

        for ($attempt = 1; $attempt <= 4; $attempt++) {
            $this->from(route('register.otp.notice'))
                ->post(route('register.otp.verify'), ['code' => $wrongOtp])
                ->assertRedirect(route('register.otp.notice'))
                ->assertSessionHasErrors('code');
        }

        $this->post(route('register.otp.verify'), ['code' => $wrongOtp])
            ->assertRedirect(route('register'))
            ->assertSessionMissing('registration_otp');

        $this->assertDatabaseMissing('users', ['email' => 'baru@example.test']);
    }

    public function test_role_middleware_separates_admin_and_buyer_areas(): void
    {
        $buyer = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($buyer)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($admin)->get(route('cart.index'))->assertForbidden();
    }

    public function test_admin_can_open_unified_seller_pages(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertSee('Dashboard Koperasi');
        $this->actingAs($admin)->get(route('admin.buyers.index'))->assertOk()->assertSee('Akun Pembeli');
        $this->actingAs($admin)->get(route('admin.reports.sales'))->assertOk()->assertSee('Laporan Penjualan');
    }

    public function test_buyer_can_open_buyer_dashboard(): void
    {
        $buyer = User::factory()->create();

        $this->actingAs($buyer)
            ->get(route('buyer.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Pembeli')
            ->assertSee($buyer->name)
            ->assertSee('data-confirm=', false);
    }
}
