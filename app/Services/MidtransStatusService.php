<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Services\Payments\MidtransPaymentGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Midtrans\Transaction;
use Throwable;

class MidtransStatusService
{
    public function __construct(
        private readonly MidtransPaymentGateway $gateway,
        private readonly OrderNotificationService $notifications,
    ) {}

    public function syncByReference(string $reference): bool
    {
        try {
            $this->gateway->boot();
            $status = Transaction::status($reference);
        } catch (Throwable $exception) {
            Log::warning('Sinkronisasi status Midtrans gagal.', [
                'reference' => $reference,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }

        $order = Order::query()
            ->where('payment_reference', $reference)
            ->orWhere('invoice_number', $reference)
            ->first();

        if (! $order || empty($status->transaction_status)) {
            return false;
        }

        return $this->applyTransactionStatus(
            $order,
            $status->transaction_status,
            $status->fraud_status ?? null,
        );
    }

    public function applyTransactionStatus(Order $order, string $transactionStatus, ?string $fraudStatus = null): bool
    {
        $notificationType = null;
        $changed = DB::transaction(function () use ($order, $transactionStatus, $fraudStatus, &$notificationType) {
            $lockedOrder = Order::query()->with('items')->lockForUpdate()->find($order->id);

            if (! $lockedOrder) {
                return false;
            }

            if ($this->isSuccessful($transactionStatus, $fraudStatus)) {
                $changed = $this->markPaid($lockedOrder);
                $notificationType = $changed ? 'paid' : null;

                return $changed;
            }

            if (in_array($transactionStatus, ['cancel', 'deny', 'expire', 'failure'], true)) {
                $changed = $this->markFailed($lockedOrder, $transactionStatus);
                $notificationType = $changed ? 'failed' : null;

                return $changed;
            }

            if ($transactionStatus === 'pending'
                || ($transactionStatus === 'capture' && $fraudStatus === 'challenge')) {
                if ($lockedOrder->payment_status !== PaymentStatus::Paid
                    && $lockedOrder->payment_status !== PaymentStatus::Pending) {
                    $lockedOrder->update(['payment_status' => PaymentStatus::Pending]);

                    return true;
                }
            }

            return false;
        });

        if ($changed && $notificationType) {
            $updatedOrder = $order->fresh(['buyer']);
            $notificationType === 'paid'
                ? $this->notifications->paymentConfirmed($updatedOrder, true)
                : $this->notifications->paymentFailed($updatedOrder);
        }

        return $changed;
    }

    private function markPaid(Order $order): bool
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
        $order->update([
            'payment_status' => PaymentStatus::Paid,
            'status' => OrderStatus::Processing,
            'paid_at' => $order->paid_at ?: now(),
        ]);

        $this->record(
            $order,
            $from,
            OrderStatus::Processing,
            'midtrans_payment_confirmed',
            'Pembayaran dikonfirmasi otomatis oleh Midtrans.',
        );

        return true;
    }

    private function markFailed(Order $order, string $transactionStatus): bool
    {
        if ($order->payment_status === PaymentStatus::Paid) {
            return false;
        }

        $targetPaymentStatus = $transactionStatus === 'expire'
            ? PaymentStatus::Expired
            : PaymentStatus::Failed;

        if ($order->status === OrderStatus::Cancelled && $order->payment_status === $targetPaymentStatus) {
            return false;
        }

        $from = $order->status;
        $note = match ($transactionStatus) {
            'expire' => 'Pembayaran kedaluwarsa di Midtrans.',
            'cancel' => 'Pembayaran dibatalkan di Midtrans.',
            default => 'Pembayaran gagal atau ditolak oleh Midtrans.',
        };

        $order->update([
            'payment_status' => $targetPaymentStatus,
            'status' => OrderStatus::Cancelled,
        ]);

        $this->record($order, $from, OrderStatus::Cancelled, 'midtrans_payment_failed', $note);

        return true;
    }

    private function isSuccessful(string $transactionStatus, ?string $fraudStatus): bool
    {
        return $transactionStatus === 'settlement'
            || ($transactionStatus === 'capture' && in_array($fraudStatus, [null, 'accept'], true));
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
