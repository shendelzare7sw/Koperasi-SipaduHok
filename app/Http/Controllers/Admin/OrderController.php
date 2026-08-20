<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\StoreSetting;
use App\Services\OrderWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = Order::with('buyer')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where(fn ($nested) => $nested
                    ->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('student_name', 'like', "%{$search}%"));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.orders.index', ['orders' => $orders, 'statuses' => OrderStatus::cases()]);
    }

    public function show(Order $order): View
    {
        return view('admin.orders.show', ['order' => $order->load(['items.review.buyer', 'buyer', 'histories.actor', 'dispatchProofs', 'deliveryProofs'])]);
    }

    public function invoice(Order $order): View
    {
        return view('orders.invoice', ['order' => $order->load(['items', 'buyer'])]);
    }

    public function label(Order $order): View
    {
        return view('orders.label', [
            'order' => $order->load(['items', 'buyer']),
            'settings' => StoreSetting::values(),
        ]);
    }

    public function confirmPayment(Request $request, Order $order, OrderWorkflowService $workflow): RedirectResponse
    {
        abort_unless($order->payment_gateway === 'placeholder', 422, 'Pembayaran Paywuz harus dikonfirmasi melalui webhook gateway.');

        $workflow->confirmPayment($order, $request->user());

        return back()->with('success', 'Pembayaran dikonfirmasi; stok telah dikurangi dan pesanan mulai diproses.');
    }

    public function updateStatus(Request $request, Order $order, OrderWorkflowService $workflow): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in([OrderStatus::Ready->value, OrderStatus::OutForDelivery->value])],
            'note' => ['nullable', 'string', 'max:500'],
            'dispatch_proofs' => [Rule::requiredIf($request->input('status') === OrderStatus::OutForDelivery->value), 'nullable', 'array', 'min:1', 'max:5'],
            'dispatch_proofs.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $target = OrderStatus::from($validated['status']);

        if ($target === OrderStatus::Ready) {
            $workflow->markReady($order, $request->user(), $validated['note'] ?? null);

            return back()->with('success', 'Pesanan ditandai siap dikirim.');
        }

        $proofPaths = collect($request->file('dispatch_proofs'))
            ->map(fn ($file) => $file->store('dispatch-proofs', 'public'))
            ->all();

        try {
            $workflow->markOutForDelivery($order, $request->user(), $proofPaths, $validated['note'] ?? null);
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($proofPaths);
            throw $exception;
        }

        return back()->with('success', 'Bukti pengiriman tersimpan dan kurir mulai mengantar pesanan.');
    }

    public function markDelivered(Request $request, Order $order, OrderWorkflowService $workflow): RedirectResponse
    {
        $validated = $request->validate([
            'delivery_proofs' => ['required', 'array', 'min:1', 'max:5'],
            'delivery_proofs.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'delivery_note' => ['nullable', 'string', 'max:500'],
        ]);

        $proofPaths = collect($request->file('delivery_proofs'))
            ->map(fn ($file) => $file->store('delivery-proofs', 'public'))
            ->all();

        try {
            $workflow->markDelivered($order, $request->user(), $proofPaths, $validated['delivery_note'] ?? null);
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($proofPaths);
            throw $exception;
        }

        return back()->with('success', 'Bukti paket tiba berhasil diunggah. Menunggu konfirmasi pembeli.');
    }
}
