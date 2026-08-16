<?php

namespace App\Http\Controllers;

use App\Contracts\PaymentGateway;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Services\MidtransStatusService;
use App\Services\OrderWorkflowService;
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

    public function payment(Request $request, Order $order, PaymentGateway $gateway): View|RedirectResponse
    {
        $this->authorizeOwner($request, $order);

        if ($order->payment_status === PaymentStatus::Paid) {
            return redirect()->route('orders.show', $order)->with('success', 'Pembayaran pesanan sudah diterima.');
        }

        if ($order->payment_gateway !== 'midtrans' || config('services.payment_gateway') !== 'midtrans') {
            return redirect()->route('orders.show', $order)->withErrors(['payment' => 'Pembayaran Midtrans tidak aktif untuk pesanan ini.']);
        }

        if (blank($order->payment_token)) {
            try {
                $transaction = $gateway->createTransaction($order);
                $order->update([
                    'payment_reference' => $transaction['reference'],
                    'payment_token' => $transaction['token'],
                    'payment_status' => PaymentStatus::from($transaction['status']),
                ]);
            } catch (Throwable $exception) {
                report($exception);

                return redirect()->route('orders.show', $order)->withErrors([
                    'payment' => 'Kanal Midtrans belum dapat dibuka. Silakan coba beberapa saat lagi.',
                ]);
            }
        }

        return view('orders.payment', compact('order'));
    }

    public function syncPayment(Request $request, Order $order, MidtransStatusService $midtrans): RedirectResponse
    {
        $this->authorizeOwner($request, $order);
        abort_unless($order->payment_gateway === 'midtrans', 422);

        $changed = $midtrans->syncByReference($order->payment_reference ?: $order->invoice_number);

        return back()->with(
            'success',
            $changed ? 'Status pembayaran berhasil diperbarui.' : 'Belum ada perubahan status dari Midtrans.',
        );
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
