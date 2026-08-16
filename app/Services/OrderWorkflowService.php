<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderWorkflowService
{
    public function __construct(private readonly OrderNotificationService $notifications) {}

    public function confirmPayment(Order $order, User $actor): Order
    {
        $updatedOrder = DB::transaction(function () use ($order, $actor) {
            $lockedOrder = Order::query()->with('items')->lockForUpdate()->findOrFail($order->id);

            if ($lockedOrder->status !== OrderStatus::PendingPayment
                || ! in_array($lockedOrder->payment_status, [PaymentStatus::Unpaid, PaymentStatus::Pending], true)) {
                throw ValidationException::withMessages(['order' => 'Pembayaran pesanan ini sudah diproses.']);
            }

            foreach ($lockedOrder->items as $item) {
                $product = Product::query()->withTrashed()->lockForUpdate()->find($item->product_id);

                if (! $product || $product->trashed() || ! $product->is_active || $product->stock < $item->quantity) {
                    throw ValidationException::withMessages([
                        'order' => "Stok {$item->product_name} tidak mencukupi untuk mengonfirmasi pembayaran.",
                    ]);
                }

                $product->decrement('stock', $item->quantity);
            }

            $from = $lockedOrder->status;
            $lockedOrder->update([
                'payment_status' => PaymentStatus::Paid,
                'status' => OrderStatus::Processing,
                'paid_at' => now(),
            ]);

            $this->record($lockedOrder, $actor, $from, OrderStatus::Processing, 'payment_confirmed', 'Pembayaran dikonfirmasi secara internal.');

            return $lockedOrder->fresh(['items', 'buyer']);
        });

        $this->notifications->paymentConfirmed($updatedOrder);

        return $updatedOrder;
    }

    public function transition(Order $order, OrderStatus $target, User $actor, ?string $note = null): Order
    {
        $updatedOrder = DB::transaction(function () use ($order, $target, $actor, $note) {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            $from = $lockedOrder->status;
            $allowedTargets = $this->allowedTargets($lockedOrder);

            if (! in_array($target, $allowedTargets, true)) {
                throw ValidationException::withMessages([
                    'status' => "Status {$from->label()} tidak dapat diubah menjadi {$target->label()}.",
                ]);
            }

            $timestamps = match ($target) {
                OrderStatus::Ready => ['ready_at' => now()],
                OrderStatus::OutForDelivery => ['dispatched_at' => now()],
                OrderStatus::Delivered => ['delivered_at' => now()],
                OrderStatus::Completed => ['received_confirmed_at' => now(), 'completed_at' => now()],
                default => [],
            };

            $lockedOrder->update(['status' => $target, ...$timestamps]);
            $action = $target === OrderStatus::Completed ? 'receipt_confirmed' : 'status_updated';
            $this->record($lockedOrder, $actor, $from, $target, $action, $note);

            return $lockedOrder->fresh(['items', 'buyer', 'histories.actor']);
        });

        if ($target === OrderStatus::Completed) {
            $this->notifications->receiptConfirmed($updatedOrder);
        } else {
            $this->notifications->statusChanged($updatedOrder);
        }

        return $updatedOrder;
    }

    /** @return list<OrderStatus> */
    private function allowedTargets(Order $order): array
    {
        return match ($order->status) {
            OrderStatus::Processing => [OrderStatus::Ready],
            OrderStatus::Ready => [OrderStatus::OutForDelivery],
            OrderStatus::OutForDelivery => [OrderStatus::Delivered],
            OrderStatus::Delivered => [OrderStatus::Completed],
            default => [],
        };
    }

    public function markDelivered(Order $order, User $actor, string $proofPath, ?string $note = null): Order
    {
        $updatedOrder = DB::transaction(function () use ($order, $actor, $proofPath, $note) {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($lockedOrder->status !== OrderStatus::OutForDelivery) {
                throw ValidationException::withMessages([
                    'status' => 'Bukti tiba hanya dapat diunggah saat pesanan sedang diantar.',
                ]);
            }

            $from = $lockedOrder->status;
            $lockedOrder->update([
                'status' => OrderStatus::Delivered,
                'delivered_at' => now(),
                'delivery_proof_path' => $proofPath,
                'delivery_note' => $note,
            ]);

            $this->record(
                $lockedOrder,
                $actor,
                $from,
                OrderStatus::Delivered,
                'delivery_proof_uploaded',
                $note ?: 'Admin mengunggah bukti paket tiba.',
            );

            return $lockedOrder->fresh(['items', 'buyer', 'histories.actor']);
        });

        $this->notifications->statusChanged($updatedOrder);

        return $updatedOrder;
    }

    private function record(
        Order $order,
        User $actor,
        OrderStatus $from,
        OrderStatus $to,
        string $action,
        ?string $note,
    ): void {
        OrderStatusHistory::create([
            'order_id' => $order->id,
            'user_id' => $actor->id,
            'from_status' => $from->value,
            'to_status' => $to->value,
            'action' => $action,
            'note' => $note,
        ]);
    }
}
