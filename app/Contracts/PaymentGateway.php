<?php

namespace App\Contracts;

use App\Models\Order;

interface PaymentGateway
{
    /**
     * @return array{reference: string, token: string|null, status: string}
     */
    public function createTransaction(Order $order): array;
}
