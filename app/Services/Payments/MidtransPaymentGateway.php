<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Models\Order;
use Illuminate\Support\Str;
use Midtrans\Config;
use Midtrans\Snap;
use RuntimeException;

class MidtransPaymentGateway implements PaymentGateway
{
    public function __construct(private readonly PaymentConfiguration $configuration) {}

    public function createTransaction(Order $order): array
    {
        $this->boot();
        $order->loadMissing(['items', 'buyer']);

        $itemDetails = $order->items->map(fn ($item) => [
            'id' => 'product-'.$item->product_id,
            'price' => $item->unit_price,
            'quantity' => $item->quantity,
            'name' => Str::limit($item->product_name, 50, ''),
        ])->values()->all();

        if ($order->shipping_cost > 0) {
            $itemDetails[] = [
                'id' => 'shipping',
                'price' => $order->shipping_cost,
                'quantity' => 1,
                'name' => Str::limit($order->courier_name ?: 'Kurir Toko', 50, ''),
            ];
        }

        $token = Snap::getSnapToken([
            'transaction_details' => [
                'order_id' => $order->invoice_number,
                'gross_amount' => $order->total,
            ],
            'item_details' => $itemDetails,
            'customer_details' => [
                'first_name' => Str::limit($order->buyer_name, 50, ''),
                'email' => $order->buyer?->email,
                'phone' => $order->phone,
                'shipping_address' => [
                    'first_name' => Str::limit($order->buyer_name, 50, ''),
                    'phone' => $order->phone,
                    'address' => Str::limit($order->delivery_address, 200, ''),
                ],
            ],
            'callbacks' => [
                'finish' => route('orders.show', $order),
            ],
            'expiry' => [
                'start_time' => now()->format('Y-m-d H:i:s O'),
                'unit' => 'hours',
                'duration' => 24,
            ],
        ]);

        return [
            'reference' => $order->invoice_number,
            'token' => $token,
            'status' => 'pending',
        ];
    }

    public function boot(): void
    {
        $serverKey = $this->configuration->serverKey();

        if (blank($serverKey)) {
            throw new RuntimeException('MIDTRANS_SERVER_KEY belum dikonfigurasi.');
        }

        Config::$serverKey = $serverKey;
        Config::$isProduction = $this->configuration->isProduction();
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }
}
