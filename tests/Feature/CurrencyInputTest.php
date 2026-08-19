<?php

namespace Tests\Feature;

use App\Models\Courier;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrencyInputTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_price_uses_placeholder_and_accepts_indonesian_thousand_separator(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.products.create'))
            ->assertOk()
            ->assertSee('placeholder="0"', false)
            ->assertSee('data-rupiah-input', false);

        $this->actingAs($admin)->post(route('admin.products.store'), [
            'name' => 'Buku Format Rupiah',
            'description' => 'Produk pengujian format rupiah.',
            'price' => '25.000',
            'stock' => 10,
            'category' => 'buku',
            'is_active' => '1',
        ])->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('products', [
            'name' => 'Buku Format Rupiah',
            'price' => 25000,
        ]);
    }

    public function test_courier_fee_accepts_indonesian_thousand_separator(): void
    {
        $admin = User::factory()->admin()->create();
        Courier::create([
            'code' => 'main',
            'name' => 'Kurir Toko',
            'fee' => 0,
            'estimate' => '1 hari sekolah',
            'is_active' => true,
        ]);

        $this->actingAs($admin)->put(route('admin.courier.update'), [
            'name' => 'Kurir Toko',
            'fee' => '12.500',
            'estimate' => '1 hari sekolah',
            'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('couriers', [
            'code' => 'main',
            'fee' => 12500,
        ]);
    }

    public function test_catalog_price_filter_accepts_formatted_value(): void
    {
        Product::factory()->create(['name' => 'Produk Murah', 'price' => 5000, 'is_active' => true]);
        Product::factory()->create(['name' => 'Produk Mahal', 'price' => 15000, 'is_active' => true]);

        $this->get(route('catalog.index', ['min_price' => '10.000']))
            ->assertOk()
            ->assertDontSee('Produk Murah')
            ->assertSee('Produk Mahal');
    }
}
