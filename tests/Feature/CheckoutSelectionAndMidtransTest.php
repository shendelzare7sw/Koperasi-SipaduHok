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
use Tests\TestCase;

class CheckoutSelectionAndMidtransTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_redirects_buyer_without_address_to_address_management(): void
    {
        $buyer = User::factory()->create();
        $product = Product::factory()->create(['stock' => 10]);
        $cartItem = CartItem::create([
            'user_id' => $buyer->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->actingAs($buyer)
            ->get(route('checkout.create', ['items' => [$cartItem->id]]))
            ->assertRedirect(route('account.addresses.index', ['checkout_items' => [$cartItem->id]]))
            ->assertSessionHasErrors('address');
    }

    public function test_buy_now_selects_only_the_requested_product(): void
    {
        config()->set('services.payment_gateway', 'placeholder');

        $buyer = User::factory()->create();
        $buyer->addresses()->create([
            'label' => 'Rumah',
            'recipient_name' => $buyer->name,
            'phone' => $buyer->phone,
            'full_address' => 'Jl. Pendidikan No. 1',
            'village' => 'Sukamaju',
            'district' => 'Cendekia',
            'city' => 'Bandung',
            'province' => 'Jawa Barat',
            'postal_code' => '40123',
            'is_primary' => true,
        ]);
        $firstProduct = Product::factory()->create(['stock' => 10]);
        $secondProduct = Product::factory()->create(['stock' => 10]);
        $savedItem = CartItem::create([
            'user_id' => $buyer->id,
            'product_id' => $secondProduct->id,
            'quantity' => 3,
        ]);

        $response = $this->actingAs($buyer)->post(route('checkout.buy-now', $firstProduct), [
            'quantity' => 2,
        ]);

        $buyNowItem = CartItem::where('user_id', $buyer->id)->where('product_id', $firstProduct->id)->firstOrFail();
        $response->assertRedirect(route('checkout.create', ['items' => [$buyNowItem->id]]));
        $this->assertDatabaseHas('cart_items', ['id' => $savedItem->id, 'quantity' => 3]);

        $this->actingAs($buyer)
            ->get(route('checkout.create', ['items' => [$buyNowItem->id]]))
            ->assertOk()
            ->assertSee($firstProduct->name)
            ->assertDontSee($secondProduct->name);
    }

    public function test_checkout_keeps_unselected_cart_items(): void
    {
        config()->set('services.payment_gateway', 'placeholder');

        $buyer = User::factory()->create();
        $address = $buyer->addresses()->create([
            'label' => 'Rumah',
            'recipient_name' => 'Pembeli',
            'phone' => '08123456789',
            'full_address' => 'Jl. Pengiriman No. 2',
            'village' => 'Sukamaju',
            'district' => 'Cendekia',
            'city' => 'Bandung',
            'province' => 'Jawa Barat',
            'postal_code' => '40123',
            'is_primary' => true,
        ]);
        $selectedProduct = Product::factory()->create(['price' => 20000, 'stock' => 10]);
        $savedProduct = Product::factory()->create(['price' => 50000, 'stock' => 10]);
        $selectedItem = CartItem::create(['user_id' => $buyer->id, 'product_id' => $selectedProduct->id, 'quantity' => 2]);
        $savedItem = CartItem::create(['user_id' => $buyer->id, 'product_id' => $savedProduct->id, 'quantity' => 1]);
        Courier::create(['code' => 'main', 'name' => 'Kurir Toko', 'fee' => 10000, 'is_active' => true]);

        $this->actingAs($buyer)->post(route('checkout.store'), [
            'address_id' => $address->id,
            'student_name' => 'Siswa',
            'class_name' => 'VII-A',
            'payment_method' => 'qris',
            'cart_item_ids' => [$selectedItem->id],
        ])->assertRedirect();

        $order = Order::firstOrFail();
        $this->assertSame(50000, $order->total);
        $this->assertDatabaseMissing('cart_items', ['id' => $selectedItem->id]);
        $this->assertDatabaseHas('cart_items', ['id' => $savedItem->id]);
        $this->assertCount(1, $order->items);
    }

    public function test_valid_midtrans_notification_marks_order_paid_only_once(): void
    {
        config()->set('services.midtrans.server_key', 'server-test-key');

        $buyer = User::factory()->create();
        $product = Product::factory()->create(['stock' => 10]);
        $courier = Courier::create(['code' => 'main', 'name' => 'Kurir Toko', 'fee' => 10000, 'is_active' => true]);
        $order = Order::create([
            'invoice_number' => 'KSP-TEST-MIDTRANS',
            'user_id' => $buyer->id,
            'buyer_name' => $buyer->name,
            'student_name' => 'Siswa',
            'class_name' => 'VII-A',
            'phone' => $buyer->phone,
            'courier_id' => $courier->id,
            'courier_name' => $courier->name,
            'shipping_cost' => 10000,
            'delivery_address' => 'Alamat pengiriman',
            'status' => OrderStatus::PendingPayment,
            'payment_method' => 'qris',
            'payment_gateway' => 'midtrans',
            'payment_status' => PaymentStatus::Pending,
            'payment_reference' => 'KSP-TEST-MIDTRANS',
            'subtotal' => 40000,
            'total' => 50000,
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_price' => 20000,
            'quantity' => 2,
            'subtotal' => 40000,
        ]);

        $payload = [
            'order_id' => $order->invoice_number,
            'status_code' => '200',
            'gross_amount' => '50000.00',
            'transaction_status' => 'settlement',
        ];
        $payload['signature_key'] = hash('sha512', $payload['order_id'].$payload['status_code'].$payload['gross_amount'].'server-test-key');

        $this->postJson(route('payments.midtrans.notification'), $payload)->assertOk();
        $this->postJson(route('payments.midtrans.notification'), $payload)->assertOk();

        $this->assertSame(PaymentStatus::Paid, $order->fresh()->payment_status);
        $this->assertSame(OrderStatus::Processing, $order->fresh()->status);
        $this->assertSame(8, $product->fresh()->stock);
        $this->assertSame(1, $order->histories()->where('action', 'midtrans_payment_confirmed')->count());
        $this->assertSame(1, $buyer->notifications()->count(), 'Callback berulang tidak boleh menggandakan notifikasi.');
    }

    public function test_midtrans_notification_rejects_invalid_signature(): void
    {
        config()->set('services.midtrans.server_key', 'server-test-key');

        $this->postJson(route('payments.midtrans.notification'), [
            'order_id' => 'UNKNOWN',
            'status_code' => '200',
            'gross_amount' => '10000.00',
            'transaction_status' => 'settlement',
            'signature_key' => 'invalid',
        ])->assertForbidden();
    }
}
