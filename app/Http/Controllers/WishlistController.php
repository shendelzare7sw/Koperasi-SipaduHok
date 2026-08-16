<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WishlistController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::query()
            ->with('images')
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->join('wishlists', 'wishlists.product_id', '=', 'products.id')
            ->where('wishlists.user_id', $request->user()->id)
            ->where('products.is_active', true)
            ->select('products.*')
            ->latest('wishlists.created_at')
            ->paginate(12);

        return view('wishlist.index', [
            'products' => $products,
            'categories' => Product::CATEGORIES,
        ]);
    }

    public function store(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->is_active, 404);

        $request->user()->wishlists()->firstOrCreate(['product_id' => $product->id]);

        return back()->with('success', 'Produk ditambahkan ke wishlist.');
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $request->user()->wishlists()->where('product_id', $product->id)->delete();

        return back()->with('success', 'Produk dihapus dari wishlist.');
    }
}
