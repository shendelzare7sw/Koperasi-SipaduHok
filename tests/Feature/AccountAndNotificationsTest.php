<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Courier;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Notifications\OrderActivityNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountAndNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_buyer_share_profile_and_security_settings(): void
    {
        foreach ([User::factory()->create(), User::factory()->admin()->create()] as $user) {
            $this->actingAs($user)
                ->get(route('account.profile.edit'))
                ->assertOk()
                ->assertSee('Profil Saya')
                ->assertSee($user->role->label());

            $this->patch(route('account.profile.update'), [
                'name' => 'Nama Diperbarui '.$user->id,
                'email' => "akun{$user->id}@example.test",
                'phone' => '08120000000'.$user->id,
                'role' => $user->isAdmin() ? 'pembeli' : 'admin',
            ])->assertRedirect();

            $user->refresh();
            $this->assertSame('Nama Diperbarui '.$user->id, $user->name);
            $this->assertSame($user->isAdmin() ? 'admin' : 'pembeli', $user->role->value);
        }

        $buyer = User::query()->where('role', 'pembeli')->firstOrFail();
        $this->actingAs($buyer)->put(route('account.security.update'), [
            'current_password' => 'password',
            'password' => 'baruaman123',
            'password_confirmation' => 'baruaman123',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('baruaman123', $buyer->fresh()->password));
    }

    public function test_notification_center_is_scoped_to_the_authenticated_user(): void
    {
        $owner = User::factory()->create();
        $otherBuyer = User::factory()->create();
        $order = $this->createOrder($owner);
        $owner->notify(new OrderActivityNotification(
            $order,
            'Pesanan diperbarui',
            'Pesanan sedang diproses.',
        ));

        $notification = $owner->notifications()->firstOrFail();

        $this->actingAs($owner)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Pesanan diperbarui')
            ->assertSee('fa-bell', false);

        $this->actingAs($otherBuyer)
            ->post(route('notifications.open', $notification))
            ->assertForbidden();

        $this->actingAs($owner)
            ->post(route('notifications.open', $notification))
            ->assertRedirect(route('orders.show', $order));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_mobile_header_has_notification_and_account_dropdowns_for_both_roles(): void
    {
        $buyer = User::factory()->create();
        $admin = User::factory()->admin()->create();

        foreach ([
            [$buyer, route('buyer.dashboard')],
            [$buyer, route('cart.index')],
            [$admin, route('admin.dashboard')],
            [$admin, route('admin.products.index')],
        ] as [$user, $url]) {
            $this->actingAs($user)
                ->get($url)
                ->assertOk()
                ->assertSee('data-mobile-header-actions', false)
                ->assertSee('aria-label="Buka notifikasi"', false)
                ->assertSee('aria-label="Buka menu akun"', false)
                ->assertSee('aria-label="Buka menu utama"', false);
        }

        $this->actingAs($buyer)
            ->get(route('buyer.dashboard'))
            ->assertSee('Alamat Tersimpan');
    }

    public function test_brand_logo_is_unboxed_and_child_pages_have_explicit_back_navigation(): void
    {
        $product = Product::factory()->create();

        $this->get(route('catalog.show', $product))
            ->assertOk()
            ->assertSee('data-brand-logo="header"', false)
            ->assertSee('data-brand-logo="footer"', false)
            ->assertSee('data-back-link', false)
            ->assertSee('Kembali ke Katalog')
            ->assertDontSee('data-brand-logo-container', false);

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('data-back-link', false)
            ->assertSee('Kembali ke Dashboard');
    }

    private function createOrder(User $buyer): Order
    {
        $courier = Courier::create([
            'code' => 'main',
            'name' => 'Kurir Koperasi',
            'fee' => 10000,
            'is_active' => true,
        ]);

        return Order::create([
            'invoice_number' => 'KSP-NOTIF-000001',
            'user_id' => $buyer->id,
            'buyer_name' => $buyer->name,
            'student_name' => 'Siswa Contoh',
            'class_name' => 'VIII-A',
            'phone' => $buyer->phone,
            'courier_id' => $courier->id,
            'courier_name' => $courier->name,
            'shipping_cost' => $courier->fee,
            'delivery_address' => 'Alamat pembeli',
            'status' => OrderStatus::PendingPayment,
            'payment_method' => 'qris',
            'payment_status' => PaymentStatus::Pending,
            'subtotal' => 10000,
            'total' => 20000,
        ]);
    }
}
