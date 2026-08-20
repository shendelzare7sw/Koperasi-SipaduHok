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
        $this->post(route('admin.products.bulk-archive'), ['product_ids' => [$product->id]])->assertForbidden();
        $this->post(route('admin.products.archived.bulk-action'), ['action' => 'restore', 'product_ids' => [$product->id]])->assertForbidden();
    }

    public function test_admin_can_bulk_archive_and_restore_selected_products(): void
    {
        $admin = User::factory()->admin()->create();
        $products = Product::factory()->count(3)->create();

        $this->actingAs($admin)
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('name="product_ids[]"', false)
            ->assertSee('Arsipkan Terpilih');

        $this->post(route('admin.products.bulk-archive'), [
            'product_ids' => $products->take(2)->pluck('id')->all(),
        ])->assertRedirect();

        $this->assertSoftDeleted($products[0]);
        $this->assertSoftDeleted($products[1]);
        $this->assertNotSoftDeleted($products[2]);

        $this->get(route('admin.products.archived'))
            ->assertOk()
            ->assertSee('name="product_ids[]"', false)
            ->assertSee('Proses Terpilih');

        $this->post(route('admin.products.archived.bulk-action'), [
            'action' => 'restore',
            'product_ids' => $products->take(2)->pluck('id')->all(),
        ])->assertRedirect();

        $this->assertNotSoftDeleted($products[0]);
        $this->assertNotSoftDeleted($products[1]);
    }

    public function test_admin_can_permanently_delete_multiple_archived_products_and_images(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $products = Product::factory()->count(2)->create();

        foreach ($products as $index => $product) {
            $path = "products/bulk-{$index}.jpg";
            Storage::disk('public')->put($path, 'image');
            $product->images()->create(['image_path' => $path, 'is_primary' => true, 'sort_order' => 0]);
            $product->update(['image_path' => $path]);
            $product->delete();
        }

        $this->actingAs($admin)->post(route('admin.products.archived.bulk-action'), [
            'action' => 'force_delete',
            'product_ids' => $products->pluck('id')->all(),
        ])->assertRedirect();

        foreach ($products as $index => $product) {
            $this->assertDatabaseMissing('products', ['id' => $product->id]);
            Storage::disk('public')->assertMissing("products/bulk-{$index}.jpg");
        }
    }
}
