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
use Illuminate\Validation\ValidationException;
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

    public function show(Request $request, Order $order, PaywuzStatusService $payments): View
    {
        $this->authorizeOwner($request, $order);

        if ($order->payment_gateway === 'paywuz'
            && in_array($order->payment_status, [PaymentStatus::Unpaid, PaymentStatus::Pending], true)
            && filled($order->payment_reference)) {
            $payments->sync($order);
            $order->refresh();
        }

        return view('orders.show', ['order' => $order->load(['items.product', 'items.review', 'histories.actor', 'dispatchProofs', 'deliveryProofs'])]);
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
            return redirect()->route('orders.show', $order)->withErrors(['payment' => 'Layanan pembayaran digital tidak aktif untuk pesanan ini.']);
        }

        if (in_array($order->payment_status, [PaymentStatus::Failed, PaymentStatus::Expired], true)) {
            return redirect()->route('orders.show', $order)->withErrors(['payment' => 'Pembayaran ini sudah gagal, dibatalkan, atau kedaluwarsa.']);
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
                    'payment' => 'Kanal pembayaran belum dapat dibuka. Silakan coba beberapa saat lagi.',
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
            $changed ? 'Status pembayaran berhasil diperbarui.' : 'Belum ada perubahan status pembayaran.',
        );
    }

    public function changePaymentMethod(
        Request $request,
        Order $order,
        PaymentGateway $gateway,
        PaymentConfiguration $payments,
        PaywuzStatusService $paywuz,
    ): View|RedirectResponse {
        $this->authorizeOwner($request, $order);

        $guard = $this->guardChangeablePayment($order, $payments);
        if ($guard) {
            return $guard;
        }

        if (filled($order->payment_reference)) {
            try {
                $paywuz->sync($order);
                $order->refresh();
            } catch (Throwable $exception) {
                report($exception);

                return redirect()->route('orders.show', $order)->withErrors([
                    'payment' => 'Status pembayaran belum dapat diperiksa. Silakan coba ganti metode beberapa saat lagi.',
                ]);
            }
        }

        $guard = $this->guardChangeablePayment($order, $payments);
        if ($guard) {
            return $guard;
        }

        try {
            $paymentMethods = $gateway->paymentMethods();
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('orders.show', $order)->withErrors([
                'payment' => 'Daftar metode pembayaran sedang tidak dapat dimuat. Silakan coba lagi.',
            ]);
        }

        return view('orders.change-payment-method', [
            'order' => $order,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    public function updatePaymentMethod(
        Request $request,
        Order $order,
        PaymentGateway $gateway,
        PaymentConfiguration $payments,
        PaywuzStatusService $paywuz,
    ): RedirectResponse {
        $this->authorizeOwner($request, $order);

        $guard = $this->guardChangeablePayment($order, $payments);
        if ($guard) {
            return $guard;
        }

        $validated = $request->validate([
            'gateway_payment_method' => ['required', 'string', 'max:50'],
        ]);

        if (filled($order->payment_reference)) {
            try {
                $paywuz->sync($order);
                $order->refresh();
            } catch (Throwable $exception) {
                report($exception);

                return back()->withErrors([
                    'gateway_payment_method' => 'Status pembayaran belum dapat diperiksa. Silakan coba lagi.',
                ]);
            }
        }

        $guard = $this->guardChangeablePayment($order, $payments);
        if ($guard) {
            return $guard;
        }

        try {
            $paymentMethods = $gateway->paymentMethods();
        } catch (Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'gateway_payment_method' => 'Metode pembayaran sedang tidak dapat diverifikasi.',
            ]);
        }

        $selectedMethod = collect($paymentMethods)->firstWhere('code', $validated['gateway_payment_method']);
        if (! $selectedMethod) {
            throw ValidationException::withMessages([
                'gateway_payment_method' => 'Metode pembayaran tidak tersedia atau sudah dinonaktifkan.',
            ]);
        }

        if ($order->total < $selectedMethod['min_amount'] || $order->total > $selectedMethod['max_amount']) {
            throw ValidationException::withMessages([
                'gateway_payment_method' => 'Total pesanan berada di luar batas nominal metode pembayaran yang dipilih.',
            ]);
        }

        if ($order->gateway_payment_method === $selectedMethod['code'] && filled($order->payment_url)) {
            return redirect()->route('orders.payment', $order)->with('success', 'Metode ini sudah aktif. Silakan lanjutkan pembayaran.');
        }

        $oldMethod = $order->gateway_payment_method ?: '-';

        try {
            if (filled($order->payment_reference)) {
                $gateway->cancelTransaction($order);
            }

            $order->forceFill([
                'gateway_payment_method' => $selectedMethod['code'],
                'payment_reference' => null,
                'payment_url' => null,
                'gateway_total' => null,
                'payment_expires_at' => null,
                'payment_status' => PaymentStatus::Unpaid,
            ])->save();

            $transaction = $gateway->createTransaction($order->fresh(), $selectedMethod['code']);

            $order->forceFill([
                'payment_reference' => $transaction['reference'],
                'payment_url' => $transaction['payment_url'],
                'gateway_total' => $transaction['total_payment'],
                'payment_expires_at' => $transaction['expires_at'],
                'payment_status' => PaymentStatus::from($transaction['status']),
            ])->save();

            $order->histories()->create([
                'user_id' => $request->user()->id,
                'from_status' => $order->status->value,
                'to_status' => $order->status->value,
                'action' => 'payment_method_changed',
                'note' => "Metode pembayaran diganti dari {$oldMethod} ke {$selectedMethod['name']}.",
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'gateway_payment_method' => 'Metode pembayaran belum dapat diganti. Silakan coba lagi dari halaman ini.',
            ]);
        }

        return redirect()->route('orders.payment', $order->fresh())->with('success', 'Metode pembayaran berhasil diganti. Silakan selesaikan pembayaran.');
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

            return back()->withErrors(['payment' => 'Transaksi belum dapat dibatalkan melalui penyedia pembayaran.']);
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

    private function guardChangeablePayment(Order $order, PaymentConfiguration $payments): ?RedirectResponse
    {
        if ($order->payment_gateway !== 'paywuz' || ! $payments->isPaywuzEnabled()) {
            return redirect()->route('orders.show', $order)->withErrors([
                'payment' => 'Metode pembayaran hanya dapat diganti untuk pembayaran digital yang masih aktif.',
            ]);
        }

        if ($order->payment_status === PaymentStatus::Paid) {
            return redirect()->route('orders.show', $order)->with('success', 'Pembayaran pesanan sudah diterima.');
        }

        if ($order->gateway_settled_at) {
            return redirect()->route('orders.show', $order)->withErrors([
                'payment' => 'Pembayaran yang sudah dikonfirmasi gateway tidak dapat diganti.',
            ]);
        }

        if ($order->status !== OrderStatus::PendingPayment
            || in_array($order->payment_status, [PaymentStatus::Failed, PaymentStatus::Expired], true)) {
            return redirect()->route('orders.show', $order)->withErrors([
                'payment' => 'Metode pembayaran hanya dapat diganti selama pesanan masih menunggu pembayaran.',
            ]);
        }

        return null;
    }
}
