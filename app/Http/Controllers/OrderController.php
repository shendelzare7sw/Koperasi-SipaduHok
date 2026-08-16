<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

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

        return view('orders.show', ['order' => $order->load(['items', 'histories.actor'])]);
    }

    public function invoice(Request $request, Order $order): View
    {
        $this->authorizeOwner($request, $order);

        return view('orders.invoice', ['order' => $order->load(['items', 'buyer'])]);
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
