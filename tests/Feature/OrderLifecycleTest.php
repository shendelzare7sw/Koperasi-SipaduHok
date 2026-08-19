<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\CartItem;
use App\Models\Courier;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_uses_single_store_courier_and_internal_delivery_confirmation(): void
    {
        Storage::fake('public');
        $buyer = User::factory()->identityVerified()->create();
        $address = $buyer->addresses()->create([
            'label' => 'Rumah',
            'recipient_name' => 'Pembeli Toko',
            'phone' => '081234567890',
            'full_address' => 'Jl. Sekolah No. 1',
            'village' => 'Kelurahan Belajar',
            'district' => 'Kecamatan Cerdas',
            'city' => 'Jakarta Selatan',
            'province' => 'DKI Jakarta',
            'postal_code' => '12345',
            'is_primary' => true,
        ]);
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['name' => 'Buku Matematika', 'price' => 50000, 'stock' => 10]);
        Courier::create([
            'code' => 'main',
            'name' => 'Kurir Toko',
            'fee' => 12000,
            'estimate' => '1 hari sekolah',
            'is_active' => true,
        ]);
        $cartItem = CartItem::create(['user_id' => $buyer->id, 'product_id' => $product->id, 'quantity' => 2]);

        $checkout = $this->actingAs($buyer)->post(route('checkout.store'), [
            'address_id' => $address->id,
            'student_name' => 'Siswa Contoh',
            'class_name' => 'VIII-A',
            'payment_method' => 'qris',
            'notes' => 'Antar setelah jam sekolah.',
            'cart_item_ids' => [$cartItem->id],
        ]);

        $order = Order::firstOrFail();
        $checkout->assertRedirect(route('orders.show', $order));
        $this->assertSame(112000, $order->total);
        $this->assertSame('Kurir Toko', $order->courier_name);
        $this->assertStringContainsString('Kelurahan Belajar', $order->delivery_address);
        $this->assertSame(OrderStatus::PendingPayment, $order->status);
        $this->assertSame(PaymentStatus::Pending, $order->payment_status);
        $this->assertSame(10, $product->fresh()->stock, 'Stok belum berkurang sebelum pembayaran terkonfirmasi.');
        $this->assertSame(1, $admin->unreadNotifications()->count());
        $this->assertSame(1, $buyer->unreadNotifications()->count());

        $this->actingAs($admin)->post(route('admin.orders.confirm-payment', $order))->assertRedirect();
        $this->assertSame(OrderStatus::Processing, $order->fresh()->status);
        $this->assertSame(8, $product->fresh()->stock);
        $this->assertSame(2, $buyer->unreadNotifications()->count());

        $this->actingAs($admin)->patch(route('admin.orders.update-status', $order), ['status' => 'ready'])->assertRedirect();
        $this->actingAs($admin)->patch(route('admin.orders.update-status', $order), ['status' => 'out_for_delivery'])->assertRedirect();

        $this->actingAs($admin)->post(route('admin.orders.mark-delivered', $order), [
            'delivery_proof' => UploadedFile::fake()->image('bukti-tiba.jpg'),
            'delivery_note' => 'Diterima di alamat pembeli.',
        ])->assertRedirect();

        $order->refresh();
        $this->assertSame(OrderStatus::Delivered, $order->status);
        $this->assertNotNull($order->delivery_proof_path);
        $this->assertSame(5, $buyer->unreadNotifications()->count());
        Storage::disk('public')->assertExists($order->delivery_proof_path);

        $this->actingAs($buyer)->post(route('orders.confirm-received', $order))->assertRedirect();
        $this->assertSame(OrderStatus::Completed, $order->fresh()->status);
        $this->assertNotNull($order->fresh()->received_confirmed_at);
        $this->assertSame(2, $admin->unreadNotifications()->count());

        $this->actingAs($buyer)
            ->get(route('orders.invoice', $order))
            ->assertOk()
            ->assertSee($order->invoice_number)
            ->assertSee('Buku Matematika');

        $this->actingAs($admin)
            ->get(route('admin.orders.label', $order))
            ->assertOk()
            ->assertSee('Label Pengiriman')
            ->assertSee($order->delivery_address);
    }

    public function test_buyer_cannot_open_another_buyers_invoice(): void
    {
        $owner = User::factory()->create();
        $otherBuyer = User::factory()->create();
        $courier = Courier::create(['code' => 'main', 'name' => 'Kurir Toko', 'fee' => 10000, 'is_active' => true]);
        $order = Order::create([
            'invoice_number' => 'KSP-TEST-000001',
            'user_id' => $owner->id,
            'buyer_name' => $owner->name,
            'student_name' => 'Siswa A',
            'class_name' => 'VII-A',
            'phone' => $owner->phone,
            'courier_id' => $courier->id,
            'courier_name' => $courier->name,
            'shipping_cost' => $courier->fee,
            'delivery_address' => 'Alamat pemilik',
            'status' => OrderStatus::PendingPayment,
            'payment_method' => 'qris',
            'payment_status' => PaymentStatus::Pending,
            'subtotal' => 10000,
            'total' => 20000,
        ]);

        $this->actingAs($otherBuyer)->get(route('orders.invoice', $order))->assertForbidden();
    }
}
