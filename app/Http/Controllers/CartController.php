<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(Request $request): View
    {
        $items = $request->user()->cartItems()->with('product')->get();

        return view('cart.index', ['items' => $items, 'total' => $items->sum('subtotal')]);
    }

    public function store(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate(['quantity' => ['required', 'integer', 'min:1']]);
        abort_unless($product->is_active, 404);

        if ($product->price < 1 || $product->stock < 1) {
            return back()->withErrors(['quantity' => 'Produk belum dapat dibeli karena harga atau stok tidak tersedia.']);
        }

        $item = CartItem::firstOrNew(['user_id' => $request->user()->id, 'product_id' => $product->id]);
        $newQuantity = ($item->exists ? $item->quantity : 0) + $validated['quantity'];

        if ($newQuantity > $product->stock) {
            return back()->withErrors(['quantity' => 'Jumlah melebihi stok yang tersedia.']);
        }

        $item->quantity = $newQuantity;
        $item->save();

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
