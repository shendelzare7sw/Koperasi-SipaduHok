<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderShippingProof;
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
        if ($target === OrderStatus::OutForDelivery) {
            throw ValidationException::withMessages([
                'status' => 'Status dalam pengantaran wajib disertai bukti foto.',
            ]);
        }

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

    public function markReady(Order $order, User $actor, ?string $note = null): Order
    {
        return $this->transition($order, OrderStatus::Ready, $actor, $note ?: 'Pesanan selesai diproses dan siap dikirim.');
    }

    /** @param list<string> $proofPaths */
    public function markOutForDelivery(Order $order, User $actor, array $proofPaths, ?string $note = null): Order
    {
        return $this->transitionWithProof(
            $order,
            $actor,
            OrderStatus::Ready,
            OrderStatus::OutForDelivery,
            ['dispatched_at' => now()],
            OrderShippingProof::STAGE_DISPATCH,
            $proofPaths,
            'dispatch_proofs_uploaded',
            $note ?: 'Admin mengunggah bukti paket mulai diantar oleh kurir toko.',
        );
    }

    /** @param array<string, mixed> $updates */
    private function transitionWithProof(
        Order $order,
        User $actor,
        OrderStatus $requiredStatus,
        OrderStatus $target,
        array $updates,
        string $proofStage,
        array $proofPaths,
        string $action,
        string $historyNote,
    ): Order {
        $updatedOrder = DB::transaction(function () use ($order, $actor, $requiredStatus, $target, $updates, $proofStage, $proofPaths, $action, $historyNote) {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($lockedOrder->status !== $requiredStatus) {
                throw ValidationException::withMessages([
                    'status' => "Status {$lockedOrder->status->label()} tidak dapat diubah menjadi {$target->label()}.",
                ]);
            }

            $lockedOrder->update(['status' => $target, ...$updates]);
            $this->storeProofs($lockedOrder, $actor, $proofStage, $proofPaths, $historyNote);
            $this->record($lockedOrder, $actor, $requiredStatus, $target, $action, $historyNote);

            return $lockedOrder->fresh(['items', 'buyer', 'histories.actor']);
        });

        $this->notifications->statusChanged($updatedOrder);

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

    /** @param list<string> $proofPaths */
    public function markDelivered(Order $order, User $actor, array $proofPaths, ?string $note = null): Order
    {
        $updatedOrder = DB::transaction(function () use ($order, $actor, $proofPaths, $note) {
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
            ]);

            $historyNote = $note ?: 'Admin mengunggah bukti paket tiba.';
            $this->storeProofs($lockedOrder, $actor, OrderShippingProof::STAGE_DELIVERY, $proofPaths, $historyNote);

            $this->record(
                $lockedOrder,
                $actor,
                $from,
                OrderStatus::Delivered,
                'delivery_proofs_uploaded',
                $historyNote,
            );

            return $lockedOrder->fresh(['items', 'buyer', 'histories.actor']);
        });

        $this->notifications->statusChanged($updatedOrder);

        return $updatedOrder;
    }

    /** @param list<string> $proofPaths */
    private function storeProofs(Order $order, User $actor, string $stage, array $proofPaths, ?string $note): void
    {
        foreach ($proofPaths as $index => $path) {
            $order->shippingProofs()->create([
                'uploaded_by' => $actor->id,
                'stage' => $stage,
                'path' => $path,
                'note' => $index === 0 ? $note : null,
                'sort_order' => $index,
            ]);
        }
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
