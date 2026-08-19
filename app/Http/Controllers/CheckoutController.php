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
use App\Services\OrderNotificationService;
use App\Services\Payments\PaymentConfiguration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class CheckoutController extends Controller
{
    public function buyNow(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        abort_unless($product->is_active, 404);

        if ($validated['quantity'] > $product->stock) {
            return back()->withErrors(['quantity' => 'Jumlah melebihi stok yang tersedia.']);
        }

        $cartItem = CartItem::query()->updateOrCreate(
            ['user_id' => $request->user()->id, 'product_id' => $product->id],
            ['quantity' => $validated['quantity']],
        );

        return redirect()->route('checkout.create', ['items' => [$cartItem->id]]);
    }

    public function create(Request $request, PaymentConfiguration $payments): View|RedirectResponse
    {
        $selectedIds = collect($request->input('items', []))
            ->filter(fn ($id) => filter_var($id, FILTER_VALIDATE_INT) !== false)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $items = $request->user()->cartItems()
            ->with('product')
            ->when($selectedIds->isNotEmpty(), fn ($query) => $query->whereIn('id', $selectedIds))
            ->get();

        if ($items->isEmpty()) {
            return redirect()->route('cart.index')->withErrors([
                'cart' => $selectedIds->isNotEmpty()
                    ? 'Produk yang dipilih tidak ditemukan di keranjang.'
                    : 'Keranjang masih kosong.',
            ]);
        }

        if (! $request->user()->isIdentityVerified()) {
            return redirect()->route('account.identity.edit')->withErrors([
                'identity' => 'Verifikasi KTP harus disetujui admin sebelum Anda dapat checkout.',
            ]);
        }

        $addresses = $request->user()->addresses()->latest('is_primary')->latest()->get();

        if ($addresses->isEmpty()) {
            return redirect()->route('account.addresses.index', [
                'checkout_items' => $items->pluck('id')->all(),
            ])->withErrors([
                'address' => 'Tambahkan alamat pengiriman terlebih dahulu sebelum melanjutkan checkout.',
            ]);
        }

        return view('checkout.create', [
            'items' => $items,
            'subtotal' => $items->sum('subtotal'),
            'courier' => Courier::where('code', 'main')->where('is_active', true)->first(),
            'paymentMethods' => PaymentMethod::cases(),
            'addresses' => $addresses,
            'paymentStatus' => $payments->status(),
        ]);
    }

    public function store(
        Request $request,
        PaymentGateway $gateway,
        OrderNotificationService $notifications,
        PaymentConfiguration $payments,
    ): RedirectResponse {
        if (! $request->user()->isIdentityVerified()) {
            throw ValidationException::withMessages([
                'identity' => 'Akun pembeli belum lolos verifikasi KTP oleh admin.',
            ]);
        }

        if (! $payments->isCheckoutReady()) {
            throw ValidationException::withMessages([
                'payment' => 'Checkout sedang dinonaktifkan karena konfigurasi Midtrans belum lengkap.',
            ]);
        }

        $validated = $request->validate([
            'address_id' => ['required', 'integer'],
            'student_name' => ['required', 'string', 'max:255'],
            'class_name' => ['required', 'string', 'max:100'],
            'payment_method' => ['required', new Enum(PaymentMethod::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
            'cart_item_ids' => ['required', 'array', 'min:1'],
            'cart_item_ids.*' => ['required', 'integer', 'distinct'],
        ]);

        $address = $request->user()->addresses()->find($validated['address_id']);

        if (! $address) {
            throw ValidationException::withMessages(['address_id' => 'Alamat tersimpan tidak valid.']);
        }

        $validated['buyer_name'] = $address->recipient_name;
        $validated['phone'] = $address->phone;
        $validated['delivery_address'] = $address->formattedAddress();

        $order = DB::transaction(function () use ($request, $validated, $payments) {
            $selectedIds = collect($validated['cart_item_ids'])->map(fn ($id) => (int) $id)->unique()->values();
            $cartItems = CartItem::query()
                ->where('user_id', $request->user()->id)
                ->whereIn('id', $selectedIds)
                ->lockForUpdate()
                ->get();

            if ($cartItems->count() !== $selectedIds->count()) {
                throw ValidationException::withMessages(['cart' => 'Ada produk pilihan yang tidak lagi tersedia di keranjang.']);
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
                throw ValidationException::withMessages(['courier' => 'Kurir toko sedang tidak tersedia.']);
            }

            $shippingCost = $courier->fee;
            $orderData = collect($validated)->except(['address_id', 'cart_item_ids'])->all();
            $order = Order::create([
                ...$orderData,
                'user_id' => $request->user()->id,
                'courier_id' => $courier->id,
                'courier_name' => $courier->name,
                'shipping_cost' => $shippingCost,
                'status' => OrderStatus::PendingPayment,
                'payment_status' => PaymentStatus::Unpaid,
                'payment_gateway' => $payments->gateway(),
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

            CartItem::where('user_id', $request->user()->id)->whereIn('id', $selectedIds)->delete();

            return $order;
        });

        $notifications->orderCreated($order);

        try {
            $transaction = $gateway->createTransaction($order);
            $order->update([
                'payment_reference' => $transaction['reference'],
                'payment_token' => $transaction['token'],
                'payment_status' => PaymentStatus::from($transaction['status']),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('orders.show', $order)->withErrors([
                'payment' => 'Pesanan tersimpan, tetapi kanal pembayaran belum dapat dibuka. Silakan coba lagi dari detail pesanan.',
            ]);
        }

        if ($order->payment_gateway === 'midtrans' && filled($order->payment_token)) {
            return redirect()->route('orders.payment', $order)->with('success', 'Pesanan berhasil dibuat. Selesaikan pembayaran melalui Midtrans.');
        }

        return redirect()->route('orders.show', $order)->with('success', 'Pesanan dibuat. Pembayaran menggunakan mode konfirmasi internal.');
    }
}
