<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CartController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $items = $request->user()->cartItems()
            ->with('product.images')
            ->latest('updated_at')
            ->get()
            ->filter(fn (CartItem $item) => $item->product !== null)
            ->values();

        return response()->json([
            'cart_count' => (int) $items->sum('quantity'),
            'subtotal' => (int) $items->sum('subtotal'),
            'items' => $items->take(5)->map(function (CartItem $item) {
                $imagePath = $item->product->primaryImagePath();

                return [
                    'id' => $item->id,
                    'name' => $item->product->name,
                    'quantity' => $item->quantity,
                    'price' => $item->product->price,
                    'subtotal' => $item->subtotal,
                    'image_url' => $imagePath ? Storage::disk('public')->url($imagePath) : null,
                    'product_url' => route('catalog.show', $item->product),
                ];
            })->values(),
            'remaining_items' => max(0, $items->count() - 5),
        ]);
    }

    public function index(Request $request): View
    {
        $items = $request->user()->cartItems()->with('product')->get();

        return view('cart.index', ['items' => $items, 'total' => $items->sum('subtotal')]);
    }

    public function store(Request $request, Product $product): RedirectResponse|JsonResponse
    {
        $validated = $request->validate(['quantity' => ['required', 'integer', 'min:1']]);
        abort_unless($product->is_active, 404);

        if ($product->price < 1 || $product->stock < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'Produk belum dapat dibeli karena harga atau stok tidak tersedia.',
            ]);
        }

        $item = CartItem::firstOrNew(['user_id' => $request->user()->id, 'product_id' => $product->id]);
        $newQuantity = ($item->exists ? $item->quantity : 0) + $validated['quantity'];

        if ($newQuantity > $product->stock) {
            throw ValidationException::withMessages([
                'quantity' => 'Jumlah melebihi stok yang tersedia.',
            ]);
        }

        $item->quantity = $newQuantity;
        $item->save();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Produk ditambahkan ke keranjang.',
                'cart_count' => (int) $request->user()->cartItems()->sum('quantity'),
                'item_quantity' => $item->quantity,
            ]);
        }

        return redirect()->route('cart.index')->with('success', 'Produk ditambahkan ke keranjang.');
    }

    public function update(Request $request, CartItem $cartItem): RedirectResponse
    {
        abort_unless($cartItem->user_id === $request->user()->id, 403);
        $validated = $request->validate(['quantity' => ['required', 'integer', 'min:1']]);
        $cartItem->load('product');

        if (! $cartItem->product || ! $cartItem->product->is_active || $cartItem->product->price < 1) {
            return back()->withErrors(['quantity' => 'Produk sudah tidak tersedia untuk dibeli.']);
        }

        if ($validated['quantity'] > $cartItem->product->stock) {
            return back()->withErrors(['quantity' => 'Jumlah melebihi stok yang tersedia.']);
        }

        $cartItem->update($validated);

        return back();
    }

    public function destroy(Request $request, CartItem $cartItem): RedirectResponse
    {
        abort_unless($cartItem->user_id === $request->user()->id, 403);
        $cartItem->delete();

        return back();
    }
}
