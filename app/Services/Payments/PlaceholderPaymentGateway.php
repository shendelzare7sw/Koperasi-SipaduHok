<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Models\Order;

class PlaceholderPaymentGateway implements PaymentGateway
{
    public function paymentMethods(): array
    {
        return [[
            'code' => 'INTERNAL',
            'name' => 'Konfirmasi Internal',
            'type' => 'internal',
            'fee_flat' => 0,
            'fee_percent_bps' => 0,
            'min_amount' => 1,
            'max_amount' => PHP_INT_MAX,
        ]];
    }

    public function createTransaction(Order $order, string $paymentMethod): array
    {
        return [
            'reference' => 'DEMO-'.$order->invoice_number,
            'status' => 'pending',
            'payment_url' => null,
            'total_payment' => $order->total,
            'expires_at' => null,
        ];
    }

    public function fetchTransaction(Order $order): array
    {
        return ['status' => $order->payment_status->value];
    }

    public function cancelTransaction(Order $order): array
    {
        return ['status' => 'cancelled'];
    }
}
