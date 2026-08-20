<?php

namespace App\Services\Payments;

use App\Models\Order;

class PaywuzTransactionValidator
{
    /** @param array<string, mixed> $transaction */
    public function amountMatches(Order $order, array $transaction): bool
    {
        $amount = (int) ($transaction['amount'] ?? 0);
        $totalPayment = (int) ($transaction['totalPayment'] ?? 0);

        if ($amount < 1 || ($totalPayment > 0 && $totalPayment < $amount)) {
            return false;
        }

        if ($order->gateway_total) {
            return $totalPayment === $order->gateway_total;
        }

        // Depending on the project's fee policy, Paywuz may return the store
        // invoice as either the gross amount or totalPayment. In both cases the
        // other value represents the gateway fee calculation.
        return $amount === $order->total || $totalPayment === $order->total;
    }
}
