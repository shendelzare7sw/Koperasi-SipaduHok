<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Address;
use App\Models\CartItem;
use App\Models\Courier;
use App\Models\Order;
use App\Models\PaymentSetting;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class CheckoutSelectionAndPaywuzTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_redirects_buyer_without_address_to_address_management(): void
    {
        $buyer = User::factory()->identityVerified()->create();
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
        [$buyer] = $this->buyerWithAddress();
        $firstProduct = Product::factory()->create(['stock' => 10]);
        $secondProduct = Product::factory()->create(['stock' => 10]);
        $savedItem = CartItem::create([
            'user_id' => $buyer->id,
            'product_id' => $secondProduct->id,
            'quantity' => 3,
        ]);

        $response = $this->actingAs($buyer)->post(route('checkout.buy-now', $firstProduct), ['quantity' => 2]);
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
        [$buyer, $address] = $this->buyerWithAddress();
        $selectedProduct = Product::factory()->create(['price' => 20000, 'stock' => 10]);
        $savedProduct = Product::factory()->create(['price' => 50000, 'stock' => 10]);
        $selectedItem = CartItem::create(['user_id' => $buyer->id, 'product_id' => $selectedProduct->id, 'quantity' => 2]);
        $savedItem = CartItem::create(['user_id' => $buyer->id, 'product_id' => $savedProduct->id, 'quantity' => 1]);
        Courier::create(['code' => 'main', 'name' => 'Kurir Toko', 'fee' => 10000, 'is_active' => true]);

        $this->actingAs($buyer)->post(route('checkout.store'), [
            'address_id' => $address->id,
            'student_name' => 'Siswa',
            'class_name' => 'VII-A',
            'payment_method' => 'payment_gateway',
            'cart_item_ids' => [$selectedItem->id],
        ])->assertRedirect();

        $order = Order::firstOrFail();
        $this->assertSame(50000, $order->total);
        $this->assertDatabaseMissing('cart_items', ['id' => $selectedItem->id]);
        $this->assertDatabaseHas('cart_items', ['id' => $savedItem->id]);
        $this->assertCount(1, $order->items);
    }

    public function test_paywuz_methods_and_transaction_are_used_during_checkout(): void
    {
        Cache::flush();
        $apiKey = $this->enablePaywuz();
        [$buyer, $address] = $this->buyerWithAddress();
        $product = Product::factory()->create(['price' => 40000, 'stock' => 10]);
        $item = CartItem::create(['user_id' => $buyer->id, 'product_id' => $product->id, 'quantity' => 1]);
        Courier::create(['code' => 'main', 'name' => 'Kurir Toko', 'fee' => 10000, 'is_active' => true]);

        Http::fake([
            'https://api.paywuz.id/v1/payment-methods' => Http::response(['data' => [
                ['code' => 'QRIS', 'name' => 'QRIS', 'type' => 'qris', 'fee' => ['flatIdr' => 290, 'percentBps' => 70], 'limits' => ['minIdr' => 10000, 'maxIdr' => 50000000]],
                ['code' => 'VA', 'name' => 'Virtual Account (Pilih Bank)', 'type' => 'meta', 'fee' => ['flatIdr' => 0, 'percentBps' => 0], 'limits' => ['minIdr' => 10000, 'maxIdr' => 50000000]],
                ['code' => 'BNIVA', 'name' => 'BNI VA', 'type' => 'virtual_account', 'fee' => ['flatIdr' => 3400, 'percentBps' => 0], 'limits' => ['minIdr' => 10000, 'maxIdr' => 50000000]],
            ]]),
            'https://api.paywuz.id/v1/transactions' => Http::response(['data' => [
                'id' => '550e8400-e29b-41d4-a716-446655440000',
                'orderId' => 'TSH-'.now()->format('Ymd').'-000001',
                'amount' => 49360,
                'fee' => ['totalIdr' => 640],
                'totalPayment' => 50000,
                'paymentMethod' => 'QRIS',
                'paymentUrl' => 'https://paywuz.id/pay/550e8400-e29b-41d4-a716-446655440000',
                'status' => 'pending',
                'expiresAt' => now()->addHour()->toISOString(),
            ]], 201),
        ]);

        $this->actingAs($buyer)
            ->get(route('checkout.create', ['items' => [$item->id]]))
            ->assertOk()
            ->assertSee('QRIS')
            ->assertSee('Virtual Account (Pilih Bank)')
            ->assertDontSee('BNI VA');

        $this->post(route('checkout.store'), [
            'address_id' => $address->id,
            'student_name' => 'Siswa',
            'class_name' => 'VII-A',
            'payment_method' => 'payment_gateway',
            'gateway_payment_method' => 'QRIS',
            'cart_item_ids' => [$item->id],
        ])->assertRedirect();

        $order = Order::firstOrFail();
        $this->assertSame('paywuz', $order->payment_gateway);
        $this->assertStringStartsWith('TSH-', $order->invoice_number);
        $this->assertSame('sandbox', $order->payment_environment);
        $this->assertSame('QRIS', $order->gateway_payment_method);
        $this->assertSame(50000, $order->gateway_total);
        $this->assertSame('https://paywuz.id/pay/550e8400-e29b-41d4-a716-446655440000', $order->payment_url);

        Http::assertSent(fn ($request) => $request->url() === 'https://api.paywuz.id/v1/transactions'
            && $request->hasHeader('Authorization', 'Bearer '.$apiKey)
            && $request['orderId'] === $order->invoice_number
            && $request['amount'] === 50000
            && $request['paymentMethod'] === 'QRIS');
    }

    public function test_settlement_confirms_customer_payment_and_later_paid_event_remains_idempotent(): void
    {
        $apiKey = $this->enablePaywuz();
        [$buyer] = $this->buyerWithAddress(false);
        $product = Product::factory()->create(['stock' => 10]);
        $order = $this->paywuzOrder($buyer, $product);

        $settlement = $this->webhookPayload($order, 'transaction.settlement', 'settlement');
        $this->sendWebhook($settlement, $apiKey, 'delivery-settlement')->assertOk();

        $this->assertSame(PaymentStatus::Paid, $order->fresh()->payment_status);
        $this->assertSame(OrderStatus::Processing, $order->fresh()->status);
        $this->assertSame(8, $product->fresh()->stock);
        $this->assertNotNull($order->fresh()->gateway_settled_at);

        $paid = $this->webhookPayload($order, 'transaction.paid', 'success');
        $this->sendWebhook($paid, $apiKey, 'delivery-paid')->assertOk();
        $this->sendWebhook($paid, $apiKey, 'delivery-paid')->assertOk()->assertJson(['message' => 'Webhook already processed.']);

        $this->assertSame(PaymentStatus::Paid, $order->fresh()->payment_status);
        $this->assertSame(OrderStatus::Processing, $order->fresh()->status);
        $this->assertSame(8, $product->fresh()->stock);
        $this->assertSame(1, $order->histories()->where('action', 'gateway_payment_confirmed')->count());
        $this->assertSame(1, $buyer->notifications()->count());
    }

    public function test_pending_payment_can_be_reopened_without_creating_another_order(): void
    {
        $this->enablePaywuz();
        [$buyer] = $this->buyerWithAddress(false);
        $product = Product::factory()->create(['stock' => 10]);
        $order = $this->paywuzOrder($buyer, $product);
        $order->update(['payment_url' => 'https://paywuz.id/pay/550e8400-e29b-41d4-a716-446655440000']);

        $this->actingAs($buyer)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee('Selesaikan pembayaran dari detail pesanan');

        $this->get(route('orders.payment', $order))
            ->assertOk()
            ->assertSee('Selesaikan Pembayaran')
            ->assertDontSee('Buka Pembayaran Paywuz');

        $this->assertDatabaseCount('orders', 1);
    }

    public function test_opening_pending_order_synchronizes_customer_settlement(): void
    {
        $this->enablePaywuz();
        [$buyer] = $this->buyerWithAddress(false);
        $product = Product::factory()->create(['stock' => 10]);
        $order = $this->paywuzOrder($buyer, $product);

        Http::fake([
            'https://api.paywuz.id/v1/transactions/KSP-TEST-PAYWUZ' => Http::response(['data' => [
                'id' => $order->payment_reference,
                'orderId' => $order->invoice_number,
                'amount' => $order->total,
                'totalPayment' => $order->total,
                'paymentMethod' => 'QRIS',
                'status' => 'settlement',
            ]]),
        ]);

        $this->actingAs($buyer)->get(route('orders.show', $order))->assertOk()->assertSee('Lunas');

        $this->assertSame(PaymentStatus::Paid, $order->fresh()->payment_status);
        $this->assertSame(8, $product->fresh()->stock);
    }

    public function test_paywuz_webhook_rejects_invalid_signature_and_amount(): void
    {
        $apiKey = $this->enablePaywuz();
        [$buyer] = $this->buyerWithAddress(false);
        $product = Product::factory()->create(['stock' => 10]);
        $order = $this->paywuzOrder($buyer, $product);
        $payload = $this->webhookPayload($order, 'transaction.paid', 'success');

        $this->sendWebhook($payload, 'wrong-secret', 'delivery-invalid')->assertForbidden();

        $mismatchedPayload = $this->webhookPayload($order, 'transaction.settlement', 'success');
        $this->sendWebhook($mismatchedPayload, $apiKey, 'delivery-mismatched-event')->assertBadRequest();

        $payload['data']['amount'] = $order->total + 1;
        $this->sendWebhook($payload, $apiKey, 'delivery-wrong-amount')->assertStatus(422);
        $this->assertSame(PaymentStatus::Pending, $order->fresh()->payment_status);
    }

    public function test_paywuz_webhook_accepts_merchant_fee_amount_when_total_payment_matches_invoice(): void
    {
        $apiKey = $this->enablePaywuz();
        [$buyer] = $this->buyerWithAddress(false);
        $product = Product::factory()->create(['stock' => 10]);
        $order = $this->paywuzOrder($buyer, $product);
        $order->update(['gateway_total' => $order->total]);

        $payload = $this->webhookPayload($order, 'transaction.paid', 'success');
        $payload['data']['amount'] = $order->total - 640;
        $payload['data']['totalPayment'] = $order->total;

        $this->sendWebhook($payload, $apiKey, 'delivery-merchant-fee')->assertOk();

        $this->assertSame(PaymentStatus::Paid, $order->fresh()->payment_status);
        $this->assertSame(8, $product->fresh()->stock);
    }

    public function test_buyer_can_securely_sync_and_cancel_paywuz_transactions(): void
    {
        $apiKey = $this->enablePaywuz();
        [$buyer] = $this->buyerWithAddress(false);
        $product = Product::factory()->create(['stock' => 10]);
        $order = $this->paywuzOrder($buyer, $product);

        Http::fake([
            'https://api.paywuz.id/v1/transactions/KSP-TEST-PAYWUZ' => Http::response(['data' => [
                'id' => $order->payment_reference,
                'orderId' => $order->invoice_number,
                'amount' => $order->total,
                'totalPayment' => $order->total + 640,
                'paymentMethod' => 'QRIS',
                'status' => 'success',
            ]]),
        ]);

        $this->actingAs($buyer)
            ->post(route('orders.sync-payment', $order))
            ->assertRedirect();

        $this->assertSame(PaymentStatus::Paid, $order->fresh()->payment_status);
        $this->assertSame(8, $product->fresh()->stock);
        Http::assertSent(fn ($request) => $request->method() === 'GET'
            && $request->hasHeader('Authorization', 'Bearer '.$apiKey));

        $secondProduct = Product::factory()->create(['stock' => 10]);
        $cancelledOrder = Order::create([
            ...$order->only([
                'user_id', 'buyer_name', 'student_name', 'class_name', 'phone', 'courier_id',
                'courier_name', 'shipping_cost', 'delivery_address', 'payment_method',
                'payment_gateway', 'gateway_payment_method', 'payment_environment', 'subtotal', 'total',
            ]),
            'invoice_number' => 'KSP-TEST-CANCEL',
            'status' => OrderStatus::PendingPayment,
            'payment_status' => PaymentStatus::Pending,
            'payment_reference' => '550e8400-e29b-41d4-a716-446655440001',
        ]);
        $cancelledOrder->items()->create([
            'product_id' => $secondProduct->id,
            'product_name' => $secondProduct->name,
            'unit_price' => 20000,
            'quantity' => 2,
            'subtotal' => 40000,
        ]);

        Http::fake([
            'https://api.paywuz.id/v1/transactions/KSP-TEST-CANCEL/cancel' => Http::response(['data' => [
                'id' => $cancelledOrder->payment_reference,
                'orderId' => $cancelledOrder->invoice_number,
                'status' => 'cancelled',
            ]]),
        ]);

        $this->post(route('orders.cancel-payment', $cancelledOrder))->assertRedirect();
        $this->assertSame(PaymentStatus::Failed, $cancelledOrder->fresh()->payment_status);
        $this->assertSame(OrderStatus::Cancelled, $cancelledOrder->fresh()->status);
        $this->assertSame(10, $secondProduct->fresh()->stock);
    }

    private function enablePaywuz(): string
    {
        $apiKey = 'pk_sand_'.str_repeat('a', 32);
        PaymentSetting::create([
            'provider' => 'paywuz',
            'is_active' => true,
            'is_production' => false,
            'sandbox_api_key' => $apiKey,
        ]);

        return $apiKey;
    }

    /** @return array{User, Address} */
    private function buyerWithAddress(bool $verified = true): array
    {
        $factory = User::factory();
        $buyer = ($verified ? $factory->identityVerified() : $factory)->create();
        $address = $buyer->addresses()->create([
            'label' => 'Rumah',
            'recipient_name' => $buyer->name,
            'phone' => $buyer->phone,
            'full_address' => 'Jl. Pendidikan No. 1',
            'village' => 'Sukamaju',
            'district' => 'Cendekia',
            'city' => 'Bandung',
            'province' => 'Jawa Barat',
            'postal_code' => '40123',
            'province_code' => '32',
            'city_code' => '32.73',
            'district_code' => '32.73.01',
            'village_code' => '32.73.01.1001',
            'latitude' => '-6.1783060',
            'longitude' => '106.6318890',
            'is_primary' => true,
        ]);

        return [$buyer, $address];
    }

    private function paywuzOrder(User $buyer, Product $product): Order
    {
        $courier = Courier::create(['code' => 'main', 'name' => 'Kurir Toko', 'fee' => 10000, 'is_active' => true]);
        $order = Order::create([
            'invoice_number' => 'KSP-TEST-PAYWUZ',
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
            'payment_method' => 'payment_gateway',
            'payment_gateway' => 'paywuz',
            'payment_status' => PaymentStatus::Pending,
            'payment_reference' => '550e8400-e29b-41d4-a716-446655440000',
            'gateway_payment_method' => 'QRIS',
            'payment_environment' => 'sandbox',
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

        return $order;
    }

    /** @return array<string, mixed> */
    private function webhookPayload(Order $order, string $event, string $status): array
    {
        return [
            'event' => $event,
            'data' => [
                'id' => $order->payment_reference,
                'orderId' => $order->invoice_number,
                'amount' => $order->total,
                'fee' => 640,
                'totalPayment' => $order->total + 640,
                'paymentMethod' => 'QRIS',
                'status' => $status,
                'paidAt' => now()->toISOString(),
            ],
            'timestamp' => now()->toISOString(),
        ];
    }

    private function sendWebhook(array $payload, string $apiKey, string $deliveryId): TestResponse
    {
        $rawBody = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $signature = 'sha256='.hash_hmac('sha256', $rawBody, $apiKey);

        return $this->call(
            'POST',
            route('payments.paywuz.webhook'),
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_PAYWUZ_SIGNATURE' => $signature,
                'HTTP_X_PAYWUZ_EVENT' => $payload['event'],
                'HTTP_X_PAYWUZ_DELIVERY' => $deliveryId,
            ],
            content: $rawBody,
        );
    }
}
