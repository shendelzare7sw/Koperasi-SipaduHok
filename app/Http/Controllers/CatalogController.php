<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(Request $request): View
    {
        $minimumPrice = $this->normaliseCurrencyFilter($request->input('min_price'));
        $maximumPrice = $this->normaliseCurrencyFilter($request->input('max_price'));

        $products = Product::query()
            ->with('images')
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->where('is_active', true)
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->string('category')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->value();
                $query->where(fn ($nested) => $nested
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%'));
            })
            ->when($minimumPrice !== null, fn ($query) => $query->where('price', '>=', $minimumPrice))
            ->when($maximumPrice !== null, fn ($query) => $query->where('price', '<=', $maximumPrice))
            ->when($request->filled('rating'), function ($query) use ($request) {
                $minimumRating = min(5, max(1, $request->integer('rating')));

                $query->whereHas('reviews')
                    ->whereRaw(
                        '(select avg(reviews.rating) from reviews where reviews.product_id = products.id) >= ?',
                        [$minimumRating]
                    );
            });

        match ($request->string('sort')->value()) {
            'price_asc' => $products->orderBy('price'),
            'price_desc' => $products->orderByDesc('price'),
            'rating' => $products->orderByDesc('reviews_avg_rating'),
            'stock' => $products->orderByDesc('stock'),
            default => $products->latest(),
        };

        $products = $products->paginate(12)
            ->withQueryString();

        $wishlistIds = Auth::check() && ! Auth::user()->isAdmin()
            ? Auth::user()->wishlists()->pluck('product_id')->all()
            : [];

        $categoryCounts = Product::query()
            ->where('is_active', true)
            ->selectRaw('category, COUNT(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        $catalogMode = collect(['search', 'category', 'min_price', 'max_price', 'rating', 'sort'])
            ->contains(fn ($key) => $request->filled($key))
            || $request->integer('page') > 1;

        return view('catalog.index', [
            'products' => $products,
            'categories' => Product::CATEGORIES,
            'categoryCounts' => $categoryCounts,
            'catalogMode' => $catalogMode,
            'wishlistIds' => $wishlistIds,
        ]);
    }

    public function show(Product $product): View
    {
        abort_unless($product->is_active, 404);
        $product->load([
            'images',
            'reviews' => fn ($query) => $query->with('buyer')->latest()->limit(6),
        ]);
        $product->loadCount('reviews');
        $product->loadAvg('reviews', 'rating');

        $isWishlisted = Auth::check() && ! Auth::user()->isAdmin()
            && Auth::user()->wishlists()->where('product_id', $product->id)->exists();

        $relatedProducts = Product::query()
            ->with('images')
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->where('is_active', true)
            ->where('category', $product->category)
            ->when($product->category === 'lainnya', fn ($query) => $query->where('custom_category', $product->custom_category))
            ->whereKeyNot($product->id)
            ->where('stock', '>', 0)
            ->latest()
            ->limit(4)
            ->get();

        $wishlistIds = Auth::check() && ! Auth::user()->isAdmin()
            ? Auth::user()->wishlists()->pluck('product_id')->all()
            : [];

        return view('catalog.show', compact('product', 'isWishlisted', 'relatedProducts', 'wishlistIds'));
    }

    private function normaliseCurrencyFilter(mixed $value): ?int
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return max(0, (int) str_replace('.', '', (string) $value));
    }
}
