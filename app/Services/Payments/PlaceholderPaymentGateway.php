<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Models\Order;

class PlaceholderPaymentGateway implements PaymentGateway
{
    public function createTransaction(Order $order): array
    {
        return [
            'reference' => 'DEMO-'.$order->invoice_number,
            'token' => null,
            'status' => 'pending',
        ];
    }
}
