<?php

namespace App\Http\Controllers;

use App\Contracts\PaymentGateway;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Services\OrderWorkflowService;
use App\Services\Payments\PaymentConfiguration;
use App\Services\PaywuzStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = Order::with('items')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return view('orders.index', compact('orders'));
    }

    public function show(Request $request, Order $order): View
    {
        $this->authorizeOwner($request, $order);

        return view('orders.show', ['order' => $order->load(['items.product', 'items.review', 'histories.actor'])]);
    }

    public function invoice(Request $request, Order $order): View
    {
        $this->authorizeOwner($request, $order);

        return view('orders.invoice', ['order' => $order->load(['items', 'buyer'])]);
    }

    public function payment(
        Request $request,
        Order $order,
        PaymentGateway $gateway,
        PaymentConfiguration $payments,
    ): View|RedirectResponse {
        $this->authorizeOwner($request, $order);

        if ($order->payment_status === PaymentStatus::Paid) {
            return redirect()->route('orders.show', $order)->with('success', 'Pembayaran pesanan sudah diterima.');
        }

        if ($order->payment_gateway !== 'paywuz' || ! $payments->isPaywuzEnabled()) {
            return redirect()->route('orders.show', $order)->withErrors(['payment' => 'Pembayaran Paywuz tidak aktif untuk pesanan ini.']);
        }

        if (in_array($order->payment_status, [PaymentStatus::Failed, PaymentStatus::Expired], true)) {
            return redirect()->route('orders.show', $order)->withErrors(['payment' => 'Transaksi Paywuz ini sudah gagal, dibatalkan, atau kedaluwarsa.']);
        }

        if (blank($order->payment_url)) {
            try {
                $transaction = $gateway->createTransaction($order, (string) $order->gateway_payment_method);
                $order->update([
                    'payment_reference' => $transaction['reference'],
                    'payment_url' => $transaction['payment_url'],
                    'gateway_total' => $transaction['total_payment'],
                    'payment_expires_at' => $transaction['expires_at'],
                    'payment_status' => PaymentStatus::from($transaction['status']),
                ]);
            } catch (Throwable $exception) {
                report($exception);

                return redirect()->route('orders.show', $order)->withErrors([
                    'payment' => 'Kanal Paywuz belum dapat dibuka. Silakan coba beberapa saat lagi.',
                ]);
            }
        }

        return view('orders.payment', ['order' => $order->fresh()]);
    }

    public function syncPayment(Request $request, Order $order, PaywuzStatusService $paywuz): RedirectResponse
    {
        $this->authorizeOwner($request, $order);
        abort_unless($order->payment_gateway === 'paywuz', 422);

        $changed = $paywuz->sync($order);

        return back()->with(
            'success',
            $changed ? 'Status pembayaran berhasil diperbarui.' : 'Belum ada perubahan status dari Paywuz.',
        );
    }

    public function cancelPayment(Request $request, Order $order, PaywuzStatusService $paywuz): RedirectResponse
    {
        $this->authorizeOwner($request, $order);
        abort_unless($order->payment_gateway === 'paywuz', 422);

        if ($order->payment_status === PaymentStatus::Paid) {
            return back()->withErrors(['payment' => 'Pembayaran yang sudah lunas tidak dapat dibatalkan.']);
        }

        if ($order->gateway_settled_at) {
            return back()->withErrors(['payment' => 'Pembayaran yang sudah dikonfirmasi gateway tidak dapat dibatalkan melalui aplikasi.']);
        }

        try {
            $paywuz->cancel($order);
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors(['payment' => 'Transaksi belum dapat dibatalkan melalui Paywuz.']);
        }

        return back()->with('success', 'Transaksi pembayaran berhasil dibatalkan.');
    }

    public function confirmReceived(Request $request, Order $order, OrderWorkflowService $workflow): RedirectResponse
    {
        $this->authorizeOwner($request, $order);
        abort_unless($order->canBeConfirmedByBuyer(), 422, 'Admin belum mengunggah bukti paket tiba.');

        $workflow->transition($order, OrderStatus::Completed, $request->user(), 'Barang diterima dan dikonfirmasi oleh pembeli.');

        return back()->with('success', 'Penerimaan pesanan berhasil dikonfirmasi.');
    }

    private function authorizeOwner(Request $request, Order $order): void
    {
        abort_unless($order->user_id === $request->user()->id, 403);
    }
}
