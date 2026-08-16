<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_product_with_named_other_category(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.products.create'))
            ->assertOk()
            ->assertSee('Lainnya')
            ->assertSee('Nama kategori tambahan')
            ->assertSee('selectedCategory', false);

        $this->actingAs($admin)->post(route('admin.products.store'), [
            'name' => 'Botol Minum Siswa',
            'description' => 'Botol minum untuk kegiatan sekolah.',
            'price' => '28.000',
            'stock' => 40,
            'category' => 'lainnya',
            'custom_category' => 'Perlengkapan Harian',
            'is_active' => '1',
        ])->assertRedirect(route('admin.products.index'));

        $product = Product::where('name', 'Botol Minum Siswa')->firstOrFail();

        $this->assertSame('lainnya', $product->category);
        $this->assertSame('Perlengkapan Harian', $product->custom_category);
        $this->assertSame('Perlengkapan Harian', $product->categoryLabel());

        $this->actingAs($admin)->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('Perlengkapan Harian');
    }

    public function test_other_category_name_is_required_and_cleared_when_category_changes(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.products.store'), [
            'name' => 'Produk Tanpa Kategori Tambahan',
            'price' => 10000,
            'stock' => 5,
            'category' => 'lainnya',
            'custom_category' => '',
            'is_active' => '1',
        ])->assertSessionHasErrors('custom_category');

        $product = Product::factory()->create([
            'category' => 'lainnya',
            'custom_category' => 'Kebutuhan Kelas',
        ]);

        $this->actingAs($admin)->put(route('admin.products.update', $product), [
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            'stock' => $product->stock,
            'category' => 'buku',
            'custom_category' => 'Nilai lama harus dibersihkan',
            'is_active' => '1',
        ])->assertRedirect(route('admin.products.index'));

        $this->assertNull($product->fresh()->custom_category);
    }
}
