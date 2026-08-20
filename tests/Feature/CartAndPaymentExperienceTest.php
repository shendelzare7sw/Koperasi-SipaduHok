<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\CartItem;
use App\Models\Courier;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartAndPaymentExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_sees_compact_product_actions_and_cart_beside_notification(): void
    {
        $buyer = User::factory()->create();
        $admin = User::factory()->admin()->create();
        Product::factory()->create(['price' => 15000, 'stock' => 5, 'is_active' => true]);

        $this->actingAs($buyer)
            ->get(route('catalog.index'))
            ->assertOk()
            ->assertSee('data-product-buy-now', false)
            ->assertSee('Beli Langsung')
            ->assertSee('data-product-add-cart', false)
            ->assertSee('fa-cart-plus', false)
            ->assertSeeInOrder(['data-header-cart', 'aria-label="Buka notifikasi"'], false);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('data-header-cart', false);
    }

    public function test_cart_uses_plus_minus_without_update_confirmation_and_rejects_zero_quantity(): void
    {
        $buyer = User::factory()->create();
        $product = Product::factory()->create(['price' => 15000, 'stock' => 5, 'is_active' => true]);
        $item = CartItem::create([
            'user_id' => $buyer->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->actingAs($buyer)
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('data-cart-decrement', false)
            ->assertSee('data-cart-increment', false)
            ->assertSee('fa-minus', false)
            ->assertSee('fa-plus', false)
            ->assertDontSee('Perbarui jumlah produk?')
            ->assertDontSee('>Update<', false);

        $this->actingAs($buyer)
            ->patch(route('cart.update', $item), ['quantity' => 0])
            ->assertSessionHasErrors('quantity');

        $this->assertSame(2, $item->fresh()->quantity);

        $this->patch(route('cart.update', $item), ['quantity' => 3])
            ->assertSessionHasNoErrors();

        $this->assertSame(3, $item->fresh()->quantity);
    }

    public function test_checkout_shows_one_gateway_choice_without_fixed_channel_radios(): void
    {
        [$buyer, $item] = $this->checkoutContext();

        $this->actingAs($buyer)
            ->get(route('checkout.create', ['items' => [$item->id]]))
            ->assertOk()
            ->assertSee('name="payment_method" value="payment_gateway"', false)
            ->assertSee('data-payment-gateway-method', false)
            ->assertDontSee('name="payment_method" value="qris"', false)
            ->assertDontSee('name="payment_method" value="virtual_account"', false);
    }

    public function test_checkout_rejects_empty_selection_zero_quantity_and_zero_price(): void
    {
        [$buyer, $item, $address] = $this->checkoutContext();
        Courier::create(['code' => 'main', 'name' => 'Kurir Toko', 'fee' => 10000, 'is_active' => true]);

        $this->actingAs($buyer)
            ->get(route('checkout.create', ['selected' => 1]))
            ->assertRedirect(route('cart.index'))
            ->assertSessionHasErrors('cart');

        $item->update(['quantity' => 0]);

        $this->get(route('checkout.create', ['items' => [$item->id]]))
            ->assertRedirect(route('cart.index'))
            ->assertSessionHasErrors('cart');

        $this->post(route('checkout.store'), [
            'address_id' => $address->id,
            'student_name' => 'Siswa',
            'class_name' => 'VII-A',
            'payment_method' => 'payment_gateway',
            'cart_item_ids' => [$item->id],
        ])->assertSessionHasErrors('cart');

        $this->assertDatabaseCount('orders', 0);

        $item->update(['quantity' => 1]);
        $item->product()->update(['price' => 0]);

        $this->post(route('checkout.store'), [
            'address_id' => $address->id,
            'student_name' => 'Siswa',
            'class_name' => 'VII-A',
            'payment_method' => 'payment_gateway',
            'cart_item_ids' => [$item->id],
        ])->assertSessionHasErrors('cart');

        $this->assertDatabaseCount('orders', 0);
    }

    /** @return array{User, CartItem, Address} */
    private function checkoutContext(): array
    {
        $buyer = User::factory()->identityVerified()->create();
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
            'is_primary' => true,
        ]);
        $product = Product::factory()->create(['price' => 15000, 'stock' => 5, 'is_active' => true]);
        $item = CartItem::create([
            'user_id' => $buyer->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        return [$buyer, $item, $address];
    }
}
