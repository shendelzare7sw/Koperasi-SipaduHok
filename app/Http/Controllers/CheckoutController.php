<?php

namespace App\Http\Controllers;

use App\Contracts\PaymentGateway;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\CartItem;
use App\Models\Courier;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $items = $request->user()->cartItems()->with('product')->get();

        if ($items->isEmpty()) {
            return redirect()->route('cart.index')->withErrors(['cart' => 'Keranjang masih kosong.']);
        }

        return view('checkout.create', [
            'items' => $items,
            'subtotal' => $items->sum('subtotal'),
            'courier' => Courier::where('code', 'main')->where('is_active', true)->first(),
            'paymentMethods' => PaymentMethod::cases(),
        ]);
    }

    public function store(Request $request, PaymentGateway $gateway): RedirectResponse
    {
        $validated = $request->validate([
            'buyer_name' => ['required', 'string', 'max:255'],
            'student_name' => ['required', 'string', 'max:255'],
            'class_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20'],
            'delivery_address' => ['required', 'string', 'max:1000'],
            'payment_method' => ['required', new Enum(PaymentMethod::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $order = DB::transaction(function () use ($request, $validated) {
            $cartItems = CartItem::query()->where('user_id', $request->user()->id)->get();

            if ($cartItems->isEmpty()) {
                throw ValidationException::withMessages(['cart' => 'Keranjang masih kosong.']);
            }

            $products = Product::query()
                ->whereIn('id', $cartItems->pluck('product_id')->sort()->values())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $subtotal = 0;
            foreach ($cartItems as $cartItem) {
                $product = $products->get($cartItem->product_id);
                if (! $product || ! $product->is_active || $cartItem->quantity > $product->stock) {
                    throw ValidationException::withMessages(['cart' => 'Ada produk yang tidak aktif atau stoknya berubah.']);
                }
                $subtotal += $product->price * $cartItem->quantity;
            }

            $courier = Courier::query()->where('code', 'main')->where('is_active', true)->lockForUpdate()->first();
            if (! $courier) {
                throw ValidationException::withMessages(['courier' => 'Kurir koperasi sedang tidak tersedia.']);
            }

            $shippingCost = $courier->fee;
            $order = Order::create([
                ...$validated,
                'user_id' => $request->user()->id,
                'courier_id' => $courier->id,
                'courier_name' => $courier->name,
                'shipping_cost' => $shippingCost,
                'status' => OrderStatus::PendingPayment,
                'payment_status' => PaymentStatus::Unpaid,
                'payment_gateway' => 'placeholder',
                'subtotal' => $subtotal,
                'total' => $subtotal + $shippingCost,
            ]);

            $order->update(['invoice_number' => 'KSP-'.now()->format('Ymd').'-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT)]);

            foreach ($cartItems as $cartItem) {
                $product = $products->get($cartItem->product_id);
                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit_price' => $product->price,
                    'quantity' => $cartItem->quantity,
                    'subtotal' => $product->price * $cartItem->quantity,
                ]);
            }

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'user_id' => $request->user()->id,
                'to_status' => OrderStatus::PendingPayment->value,
                'action' => 'order_created',
                'note' => 'Pesanan dibuat oleh pembeli.',
            ]);

            CartItem::where('user_id', $request->user()->id)->delete();

            return $order;
        });

        $transaction = $gateway->createTransaction($order);
        $order->update([
            'payment_reference' => $transaction['reference'],
            'payment_token' => $transaction['token'],
            'payment_status' => PaymentStatus::from($transaction['status']),
        ]);

        return redirect()->route('orders.show', $order)->with('success', 'Pesanan dibuat. Payment gateway masih menggunakan mode placeholder.');
    }
}
