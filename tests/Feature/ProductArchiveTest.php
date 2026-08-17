<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_archive_restore_and_permanently_delete_product(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create([
            'name' => 'Produk Arsip',
            'image_path' => 'products/arsip.jpg',
        ]);
        $product->images()->create([
            'image_path' => 'products/arsip.jpg',
            'is_primary' => true,
            'sort_order' => 0,
        ]);
        Storage::disk('public')->put('products/arsip.jpg', 'image-content');

        $this->actingAs($admin)
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect();
        $this->assertSoftDeleted($product);

        $this->get(route('admin.products.archived'))
            ->assertOk()
            ->assertSee('Produk Arsip')
            ->assertSee('Hapus');

        $this->patch(route('admin.products.restore', $product->id))->assertRedirect();
        $this->assertNotSoftDeleted($product);

        $product->delete();
        $this->delete(route('admin.products.force-destroy', $product->id))->assertRedirect();
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        Storage::disk('public')->assertMissing('products/arsip.jpg');
    }

    public function test_buyer_cannot_access_product_archive_actions(): void
    {
        $buyer = User::factory()->create();
        $product = Product::factory()->create();
        $product->delete();

        $this->actingAs($buyer)->get(route('admin.products.archived'))->assertForbidden();
        $this->patch(route('admin.products.restore', $product->id))->assertForbidden();
        $this->delete(route('admin.products.force-destroy', $product->id))->assertForbidden();
    }
}
