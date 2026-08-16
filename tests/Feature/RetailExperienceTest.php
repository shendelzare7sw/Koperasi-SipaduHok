<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\CartItem;
use App\Models\Courier;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RetailExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_saved_address_is_owned_by_buyer_and_used_at_checkout(): void
    {
        config()->set('services.payment_gateway', 'placeholder');
        $buyer = User::factory()->create();
        $otherBuyer = User::factory()->create();
        $address = $buyer->addresses()->create([
            'label' => 'Rumah',
            'recipient_name' => 'Penerima Tersimpan',
            'phone' => '081222222222',
            'full_address' => 'Alamat tersimpan yang resmi',
            'is_primary' => true,
        ]);
        $product = Product::factory()->create(['price' => 20000, 'stock' => 10]);
        $cartItem = CartItem::create(['user_id' => $buyer->id, 'product_id' => $product->id, 'quantity' => 1]);
        Courier::create(['code' => 'main', 'name' => 'Kurir Koperasi', 'fee' => 10000, 'is_active' => true]);

        $this->actingAs($otherBuyer)
            ->delete(route('account.addresses.destroy', $address))
            ->assertForbidden();

        $this->actingAs($buyer)->post(route('checkout.store'), [
            'address_id' => $address->id,
            'buyer_name' => 'Data yang dicoba diubah',
            'student_name' => 'Siswa Contoh',
            'class_name' => 'VIII-A',
            'phone' => '080000000000',
            'delivery_address' => 'Alamat yang dicoba diubah',
            'payment_method' => 'qris',
            'cart_item_ids' => [$cartItem->id],
        ])->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'user_id' => $buyer->id,
            'buyer_name' => 'Penerima Tersimpan',
            'phone' => '081222222222',
            'delivery_address' => 'Alamat tersimpan yang resmi',
        ]);
    }

    public function test_buyer_can_manage_wishlist(): void
    {
        $buyer = User::factory()->create();
        $product = Product::factory()->create(['name' => 'Buku Wishlist']);

        $this->actingAs($buyer)->post(route('wishlist.store', $product))->assertRedirect();
        $this->assertDatabaseHas('wishlists', ['user_id' => $buyer->id, 'product_id' => $product->id]);
        $this->get(route('wishlist.index'))->assertOk()->assertSee('Buku Wishlist');

        $this->delete(route('wishlist.destroy', $product))->assertRedirect();
        $this->assertDatabaseMissing('wishlists', ['user_id' => $buyer->id, 'product_id' => $product->id]);
    }

    public function test_admin_can_upload_gallery_and_edit_page_has_image_previews(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.products.store'), [
            'name' => 'Produk Galeri',
            'description' => 'Produk dengan beberapa foto.',
            'price' => 25000,
            'stock' => 5,
            'category' => 'buku',
            'is_active' => '1',
            'images' => [
                UploadedFile::fake()->image('depan.jpg'),
                UploadedFile::fake()->image('belakang.jpg'),
            ],
        ])->assertRedirect(route('admin.products.index'));

        $product = Product::where('name', 'Produk Galeri')->firstOrFail();
        $this->assertCount(2, $product->images);
        $this->assertSame($product->images->first()->image_path, $product->image_path);
        Storage::disk('public')->assertExists($product->image_path);

        $this->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('Galeri Produk')
            ->assertSee('previews', false)
            ->assertSee('fa-magnifying-glass-plus', false);
    }

    public function test_completed_order_allows_verified_review_and_admin_reply(): void
    {
        $buyer = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['name' => 'Buku Ulasan']);
        $order = $this->createCompletedOrder($buyer);
        $item = $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_price' => 20000,
            'quantity' => 1,
            'subtotal' => 20000,
        ]);

        $this->actingAs($buyer)->post(route('reviews.store', $item), [
            'rating' => 5,
            'comment' => 'Produk sesuai dan rapi.',
        ])->assertRedirect(route('orders.show', $order));

        $review = Review::firstOrFail();
        $this->assertSame(5, $review->rating);
        $this->actingAs($admin)->post(route('admin.reviews.reply', $review), [
            'admin_reply' => 'Terima kasih atas ulasannya.',
        ])->assertRedirect();

        $this->actingAs($buyer)
            ->get(route('catalog.show', $product))
            ->assertOk()
            ->assertSee('Produk sesuai dan rapi.')
            ->assertSee('Terima kasih atas ulasannya.')
            ->assertSee('Pembelian terverifikasi');

        $this->get(route('catalog.index', ['rating' => 5, 'sort' => 'rating']))
            ->assertOk()
            ->assertSee('Buku Ulasan');
    }

    private function createCompletedOrder(User $buyer): Order
    {
        $courier = Courier::create(['code' => 'main', 'name' => 'Kurir Koperasi', 'fee' => 10000, 'is_active' => true]);

        return Order::create([
            'invoice_number' => 'KSP-REVIEW-000001',
            'user_id' => $buyer->id,
            'buyer_name' => $buyer->name,
            'student_name' => 'Siswa Contoh',
            'class_name' => 'VIII-A',
            'phone' => $buyer->phone,
            'courier_id' => $courier->id,
            'courier_name' => $courier->name,
            'shipping_cost' => $courier->fee,
            'delivery_address' => 'Alamat pembeli',
            'status' => OrderStatus::Completed,
            'payment_method' => 'qris',
            'payment_status' => PaymentStatus::Paid,
            'subtotal' => 20000,
            'total' => 30000,
            'paid_at' => now(),
            'completed_at' => now(),
        ]);
    }
}
