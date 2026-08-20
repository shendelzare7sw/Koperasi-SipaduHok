<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Services\Payments\PaywuzPaymentGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class PaywuzStatusService
{
    public function __construct(
        private readonly PaywuzPaymentGateway $gateway,
        private readonly OrderNotificationService $notifications,
    ) {}

    public function sync(Order $order): bool
    {
        try {
            $transaction = $this->gateway->fetchTransaction($order);
        } catch (Throwable $exception) {
            Log::warning('Sinkronisasi status Paywuz gagal.', [
                'order_id' => $order->id,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }

        return $this->applyStatus($order, (string) $transaction['status'], $transaction);
    }

    public function cancel(Order $order): bool
    {
        $transaction = $this->gateway->cancelTransaction($order);

        return $this->applyStatus($order, (string) $transaction['status'], $transaction);
    }

    /** @param array<string, mixed> $transaction */
    public function applyStatus(Order $order, string $status, array $transaction = []): bool
    {
        $notificationType = null;
        $changed = DB::transaction(function () use ($order, $status, $transaction, &$notificationType) {
            $lockedOrder = Order::query()->with('items')->lockForUpdate()->find($order->id);

            if (! $lockedOrder || $lockedOrder->payment_gateway !== 'paywuz') {
                return false;
            }

            if (in_array($status, ['settlement', 'success'], true)) {
                $changed = $this->markPaid($lockedOrder, $status, $transaction);
                $notificationType = $changed ? 'paid' : null;

                return $changed;
            }

            if (in_array($status, ['failed', 'cancelled', 'expired'], true)) {
                $changed = $this->markFailed($lockedOrder, $status);
                $notificationType = $changed ? 'failed' : null;

                return $changed;
            }

            $updates = [];
            if ($lockedOrder->payment_status === PaymentStatus::Unpaid) {
                $updates['payment_status'] = PaymentStatus::Pending;
            }

            if (filled($transaction['paymentMethod'] ?? null)) {
                $updates['gateway_payment_method'] = (string) $transaction['paymentMethod'];
            }

            if (filled($transaction['totalPayment'] ?? null)) {
                $updates['gateway_total'] = max($lockedOrder->total, (int) $transaction['totalPayment']);
            }

            if ($updates) {
                $lockedOrder->update($updates);

                return true;
            }

            return false;
        });

        if ($changed && $notificationType) {
            $updatedOrder = $order->fresh(['buyer']);
            $notificationType === 'paid'
                ? $this->notifications->paymentConfirmed($updatedOrder, 'penyedia pembayaran')
                : $this->notifications->paymentFailed($updatedOrder);
        }

        return $changed;
    }

    /** @param array<string, mixed> $transaction */
    private function markPaid(Order $order, string $gatewayStatus, array $transaction): bool
    {
        if ($order->payment_status === PaymentStatus::Paid) {
            return false;
        }

        $products = Product::query()
            ->withTrashed()
            ->whereIn('id', $order->items->pluck('product_id')->filter()->sort()->values())
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($order->items as $item) {
            $product = $products->get($item->product_id);

            if (! $product || $product->trashed() || ! $product->is_active || $product->stock < $item->quantity) {
                throw ValidationException::withMessages([
                    'order' => "Pembayaran diterima, tetapi stok {$item->product_name} perlu ditangani admin.",
                ]);
            }
        }

        foreach ($order->items as $item) {
            $products->get($item->product_id)->decrement('stock', $item->quantity);
        }

        $from = $order->status;
        $updates = [
            'payment_status' => PaymentStatus::Paid,
            'status' => OrderStatus::Processing,
            'paid_at' => $order->paid_at ?: now(),
        ];

        if ($gatewayStatus === 'settlement' && ! $order->gateway_settled_at) {
            $updates['gateway_settled_at'] = now();
        }

        if (filled($transaction['totalPayment'] ?? null)) {
            $updates['gateway_total'] = max($order->total, (int) $transaction['totalPayment']);
        }

        $order->update($updates);

        $this->record(
            $order,
            $from,
            OrderStatus::Processing,
            'gateway_payment_confirmed',
            'Pembayaran pelanggan telah dikonfirmasi oleh penyedia pembayaran.',
        );

        return true;
    }

    private function markFailed(Order $order, string $status): bool
    {
        if ($order->payment_status === PaymentStatus::Paid) {
            return false;
        }

        $targetPaymentStatus = $status === 'expired' ? PaymentStatus::Expired : PaymentStatus::Failed;

        if ($order->status === OrderStatus::Cancelled && $order->payment_status === $targetPaymentStatus) {
            return false;
        }

        $from = $order->status;
        $note = match ($status) {
            'expired' => 'Pembayaran telah kedaluwarsa.',
            'cancelled' => 'Pembayaran dibatalkan melalui penyedia pembayaran.',
            default => 'Pembayaran gagal atau ditolak oleh penyedia pembayaran.',
        };

        $order->update([
            'payment_status' => $targetPaymentStatus,
            'status' => OrderStatus::Cancelled,
        ]);

        $this->record($order, $from, OrderStatus::Cancelled, 'paywuz_payment_failed', $note);

        return true;
    }

    private function record(Order $order, OrderStatus $from, OrderStatus $to, string $action, string $note): void
    {
        OrderStatusHistory::create([
            'order_id' => $order->id,
            'user_id' => null,
            'from_status' => $from->value,
            'to_status' => $to->value,
            'action' => $action,
            'note' => $note,
        ]);
    }
}
