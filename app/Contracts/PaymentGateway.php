<?php

namespace App\Contracts;

use App\Models\Order;

interface PaymentGateway
{
    /**
     * @return list<array{code: string, name: string, type: string, fee_flat: int, fee_percent_bps: int, min_amount: int, max_amount: int}>
     */
    public function paymentMethods(): array;

    /**
     * @return array{reference: string, status: string, payment_url: string|null, total_payment: int, expires_at: string|null}
     */
    public function createTransaction(Order $order, string $paymentMethod): array;

    /** @return array<string, mixed> */
    public function fetchTransaction(Order $order): array;

    /** @return array<string, mixed> */
    public function cancelTransaction(Order $order): array;
}
