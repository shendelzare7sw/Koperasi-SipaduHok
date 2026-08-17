<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogFilterExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_uses_compact_banner_search_and_category_shortcuts(): void
    {
        Product::factory()->create(['category' => 'buku', 'is_active' => true]);

        $this->get(route('catalog.index'))
            ->assertOk()
            ->assertSee('sm:py-7', false)
            ->assertSee('role="search"', false)
            ->assertSee('fa-book-open', false)
            ->assertSee('fa-pen-ruler', false)
            ->assertSee('fa-shirt', false)
            ->assertDontSee('data-mobile-filter-toggle', false);
    }

    public function test_search_results_have_separate_sort_and_left_mobile_filter_drawer(): void
    {
        Product::factory()->create([
            'name' => 'Buku Ensiklopedia Sekolah',
            'description' => 'Referensi pengetahuan untuk siswa.',
            'category' => 'buku',
            'is_active' => true,
        ]);

        $this->get(route('catalog.index', ['search' => 'pengetahuan']))
            ->assertOk()
            ->assertSee('Buku Ensiklopedia Sekolah')
            ->assertSee('data-mobile-filter-toggle', false)
            ->assertSee('left-4', false)
            ->assertSee('-translate-x-full', false)
            ->assertSee('Harga terendah')
            ->assertSee('Filter Produk');
    }

    public function test_catalog_sorts_price_independently_from_filters(): void
    {
        Product::factory()->create(['name' => 'Produk Harga Tinggi', 'price' => 50000, 'category' => 'buku', 'is_active' => true]);
        Product::factory()->create(['name' => 'Produk Harga Rendah', 'price' => 5000, 'category' => 'buku', 'is_active' => true]);

        $this->get(route('catalog.index', [
            'category' => 'buku',
            'sort' => 'price_asc',
        ]))
            ->assertOk()
            ->assertSeeInOrder(['Produk Harga Rendah', 'Produk Harga Tinggi'])
            ->assertSee('name="category"', false)
            ->assertSee('name="sort"', false);
    }
}
