<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderActivityNotification;
use Illuminate\Support\Facades\Notification;

class OrderNotificationService
{
    public function orderCreated(Order $order): void
    {
        $this->notifyBuyer(
            $order,
            'Pesanan berhasil dibuat',
            "Pesanan {$order->invoice_number} menunggu pembayaran.",
            'fa-bag-shopping',
        );
        $this->notifyAdmins(
            $order,
            'Pesanan baru masuk',
            "{$order->buyer_name} membuat pesanan {$order->invoice_number}.",
            'fa-cart-plus',
        );
    }

    public function paymentConfirmed(Order $order, ?string $provider = null): void
    {
        $source = $provider ?: 'admin toko';
        $this->notifyBuyer(
            $order,
            'Pembayaran diterima',
            "Pembayaran {$order->invoice_number} dikonfirmasi oleh {$source} dan sedang diproses.",
            'fa-circle-check',
        );

        if ($provider) {
            $this->notifyAdmins(
                $order,
                'Pembayaran otomatis diterima',
                "{$provider} mengonfirmasi pembayaran {$order->invoice_number}.",
                'fa-money-check-dollar',
            );
        }
    }

    public function paymentFailed(Order $order): void
    {
        $this->notifyBuyer(
            $order,
            'Pembayaran tidak berhasil',
            "Pembayaran {$order->invoice_number} gagal atau kedaluwarsa. Silakan periksa detail pesanan.",
            'fa-circle-exclamation',
        );
    }

    public function statusChanged(Order $order): void
    {
        [$title, $message, $icon] = match ($order->status) {
            OrderStatus::Ready => [
                'Pesanan siap dikirim',
                "Pesanan {$order->invoice_number} sudah disiapkan oleh toko.",
                'fa-box',
            ],
            OrderStatus::OutForDelivery => [
                'Pesanan sedang diantar',
                "Kurir toko sedang mengantar pesanan {$order->invoice_number}.",
                'fa-truck-fast',
            ],
            OrderStatus::Delivered => [
                'Paket tiba di alamat',
                "Admin telah mengunggah bukti tiba untuk {$order->invoice_number}. Mohon konfirmasi penerimaan.",
                'fa-house-circle-check',
            ],
            default => [
                'Status pesanan diperbarui',
                "Pesanan {$order->invoice_number} kini berstatus {$order->status->label()}.",
                'fa-rotate',
            ],
        };

        $this->notifyBuyer($order, $title, $message, $icon);
    }

    public function receiptConfirmed(Order $order): void
    {
        $this->notifyAdmins(
            $order,
            'Pesanan diterima pembeli',
            "Pembeli mengonfirmasi penerimaan {$order->invoice_number}; transaksi selesai.",
            'fa-handshake',
        );
    }

    public function reviewSubmitted(Order $order, string $productName): void
    {
        $this->notifyAdmins(
            $order,
            'Ulasan produk baru',
            "Pembeli memberikan ulasan untuk {$productName} pada {$order->invoice_number}.",
            'fa-star',
        );
    }

    public function reviewReplied(Order $order, string $productName): void
    {
        $this->notifyBuyer(
            $order,
            'Ulasan dibalas toko',
            "Admin membalas ulasan Anda untuk {$productName}.",
            'fa-reply',
        );
    }

    private function notifyBuyer(Order $order, string $title, string $message, string $icon): void
    {
        $order->loadMissing('buyer');
        $order->buyer?->notify(new OrderActivityNotification($order, $title, $message, $icon));
    }

    private function notifyAdmins(Order $order, string $title, string $message, string $icon): void
    {
        $admins = User::query()->where('role', UserRole::Admin->value)->get();

        if ($admins->isNotEmpty()) {
            Notification::send($admins, new OrderActivityNotification($order, $title, $message, $icon));
        }
    }
}
