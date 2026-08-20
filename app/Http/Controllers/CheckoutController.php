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
use Illuminate\Validation\Rule;
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

        if ($product->price < 1 || $product->stock < 1) {
            return back()->withErrors(['quantity' => 'Produk belum dapat dibeli karena harga atau stok tidak tersedia.']);
        }

        if ($validated['quantity'] > $product->stock) {
            return back()->withErrors(['quantity' => 'Jumlah melebihi stok yang tersedia.']);
        }

        $cartItem = CartItem::query()->updateOrCreate(
            ['user_id' => $request->user()->id, 'product_id' => $product->id],
            ['quantity' => $validated['quantity']],
        );

        return redirect()->route('checkout.create', ['items' => [$cartItem->id]]);
    }

    public function create(
        Request $request,
        PaymentConfiguration $payments,
        PaymentGateway $gateway,
    ): View|RedirectResponse {
        $selectedIds = collect($request->input('items', []))
            ->filter(fn ($id) => filter_var($id, FILTER_VALIDATE_INT) !== false)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($request->boolean('selected') && $selectedIds->isEmpty()) {
            return redirect()->route('cart.index')->withErrors([
                'cart' => 'Pilih minimal satu produk sebelum checkout.',
            ]);
        }

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

        $hasInvalidItem = $items->contains(fn (CartItem $item) => ! $item->product
            || ! $item->product->is_active
            || $item->quantity < 1
            || $item->quantity > $item->product->stock
            || $item->product->price < 1);

        if ($hasInvalidItem) {
            return redirect()->route('cart.index')->withErrors([
                'cart' => 'Periksa kembali jumlah, harga, dan stok produk sebelum checkout.',
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

        $paymentMethods = [];
        $paymentMethodsError = null;

        if ($payments->isPaywuzEnabled() && $payments->isReady()) {
            try {
                $paymentMethods = $gateway->paymentMethods();
            } catch (Throwable $exception) {
                report($exception);
                $paymentMethodsError = 'Metode pembayaran sedang tidak dapat dimuat. Silakan coba lagi.';
            }
        }

        return view('checkout.create', [
            'items' => $items,
            'subtotal' => $items->sum('subtotal'),
            'courier' => Courier::where('code', 'main')->where('is_active', true)->first(),
            'addresses' => $addresses,
            'paymentStatus' => $payments->status(),
            'paymentMethods' => $paymentMethods,
            'paymentMethodsError' => $paymentMethodsError,
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
                'payment' => 'Checkout sedang dinonaktifkan karena konfigurasi pembayaran belum lengkap.',
            ]);
        }

        $validated = $request->validate([
            'address_id' => ['required', 'integer'],
            'student_name' => ['required', 'string', 'max:255'],
            'class_name' => ['required', 'string', 'max:100'],
            'payment_method' => ['required', new Enum(PaymentMethod::class)],
            'gateway_payment_method' => [Rule::requiredIf($payments->isPaywuzEnabled()), 'nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'cart_item_ids' => ['required', 'array', 'min:1'],
            'cart_item_ids.*' => ['required', 'integer', 'distinct'],
        ]);

        $address = $request->user()->addresses()->find($validated['address_id']);

        if (! $address) {
            throw ValidationException::withMessages(['address_id' => 'Alamat tersimpan tidak valid.']);
        }

        if (collect([
            $address->province_code,
            $address->city_code,
            $address->district_code,
            $address->village_code,
            $address->latitude,
            $address->longitude,
        ])->contains(fn ($value) => blank($value))) {
            throw ValidationException::withMessages([
                'address_id' => 'Lengkapi wilayah dan titik peta pada alamat ini sebelum checkout.',
            ]);
        }

        $validated['buyer_name'] = $address->recipient_name;
        $validated['phone'] = $address->phone;
        $validated['delivery_address'] = $address->formattedAddress();

        $selectedGatewayMethod = null;
        if ($payments->isPaywuzEnabled()) {
            try {
                $selectedGatewayMethod = collect($gateway->paymentMethods())
                    ->firstWhere('code', $validated['gateway_payment_method']);
            } catch (Throwable $exception) {
                report($exception);
                throw ValidationException::withMessages([
                    'gateway_payment_method' => 'Metode pembayaran sedang tidak dapat diverifikasi.',
                ]);
            }

            if (! $selectedGatewayMethod) {
                throw ValidationException::withMessages([
                    'gateway_payment_method' => 'Metode pembayaran tidak tersedia atau sudah dinonaktifkan.',
                ]);
            }
        }

        $order = DB::transaction(function () use ($request, $validated, $payments, $selectedGatewayMethod, $address) {
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
                if (! $product
                    || ! $product->is_active
                    || $cartItem->quantity < 1
                    || $cartItem->quantity > $product->stock
                    || $product->price < 1) {
                    throw ValidationException::withMessages(['cart' => 'Ada produk dengan jumlah, harga, atau stok yang tidak valid.']);
                }
                $subtotal += $product->price * $cartItem->quantity;
            }

            if ($subtotal < 1) {
                throw ValidationException::withMessages(['cart' => 'Subtotal produk harus lebih besar dari Rp 0.']);
            }

            $courier = Courier::query()->where('code', 'main')->where('is_active', true)->lockForUpdate()->first();
            if (! $courier) {
                throw ValidationException::withMessages(['courier' => 'Kurir toko sedang tidak tersedia.']);
            }

            $shippingCost = $courier->fee;
            $total = $subtotal + $shippingCost;

            if ($selectedGatewayMethod
                && ($total < $selectedGatewayMethod['min_amount'] || $total > $selectedGatewayMethod['max_amount'])) {
                throw ValidationException::withMessages([
                    'gateway_payment_method' => 'Total pesanan berada di luar batas nominal metode pembayaran yang dipilih.',
                ]);
            }

            $orderData = collect($validated)->except(['address_id', 'cart_item_ids', 'gateway_payment_method'])->all();
            $order = Order::create([
                ...$orderData,
                'user_id' => $request->user()->id,
                'courier_id' => $courier->id,
                'courier_name' => $courier->name,
                'shipping_cost' => $shippingCost,
                'delivery_address' => $address->formattedAddress(),
                'delivery_latitude' => $address->latitude,
                'delivery_longitude' => $address->longitude,
                'delivery_maps_url' => $address->mapsUrl(),
                'status' => OrderStatus::PendingPayment,
                'payment_status' => PaymentStatus::Unpaid,
                'payment_gateway' => $payments->gateway(),
                'gateway_payment_method' => $selectedGatewayMethod['code'] ?? 'INTERNAL',
                'payment_environment' => $payments->environment(),
                'subtotal' => $subtotal,
                'total' => $total,
            ]);

            $order->update(['invoice_number' => 'TSH-'.now()->format('Ymd').'-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT)]);

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
            $transaction = $gateway->createTransaction(
                $order,
                $order->gateway_payment_method ?: 'INTERNAL',
            );
            $order->update([
                'payment_reference' => $transaction['reference'],
                'payment_url' => $transaction['payment_url'],
                'gateway_total' => $transaction['total_payment'],
                'payment_expires_at' => $transaction['expires_at'],
                'payment_status' => PaymentStatus::from($transaction['status']),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('orders.show', $order)->withErrors([
                'payment' => 'Pesanan tersimpan, tetapi kanal pembayaran belum dapat dibuka. Silakan coba lagi dari detail pesanan.',
            ]);
        }

        if ($order->payment_gateway === 'paywuz' && filled($order->payment_url)) {
            return redirect()->route('orders.payment', $order)->with('success', 'Pesanan berhasil dibuat. Silakan selesaikan pembayaran.');
        }

        return redirect()->route('orders.show', $order)->with('success', 'Pesanan dibuat. Pembayaran menggunakan mode konfirmasi internal.');
    }
}
