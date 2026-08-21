<?php

namespace App\Support;

use App\Models\Order;
use Illuminate\Support\Str;

class OrderInvoiceNumber
{
    public static function generate(): string
    {
        do {
            $invoiceNumber = 'TSH-'.now()->format('Ymd').'-'.Str::upper(Str::random(10));
        } while (Order::query()->where('invoice_number', $invoiceNumber)->exists());

        return $invoiceNumber;
    }

    public static function refreshIfUnopenedPayment(Order $order): bool
    {
        if (filled($order->payment_reference) || filled($order->payment_url)) {
            return false;
        }

        $order->forceFill(['invoice_number' => self::generate()])->save();

        return true;
    }
}
