<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\OrderItem;
use App\Services\OrderNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function edit(Request $request, OrderItem $orderItem): View
    {
        $orderItem->load(['order', 'product', 'review']);
        $this->authorizeReview($request, $orderItem);

        return view('reviews.edit', compact('orderItem'));
    }

    public function store(
        Request $request,
        OrderItem $orderItem,
        OrderNotificationService $notifications,
    ): RedirectResponse {
        $orderItem->load(['order', 'product']);
        $this->authorizeReview($request, $orderItem);
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1500'],
        ]);

        $orderItem->review()->updateOrCreate([], [
            ...$validated,
            'order_id' => $orderItem->order_id,
            'user_id' => $request->user()->id,
            'product_id' => $orderItem->product_id,
        ]);

        $notifications->reviewSubmitted($orderItem->order, $orderItem->product_name);

        return redirect()->route('orders.show', $orderItem->order)->with('success', 'Ulasan produk berhasil disimpan.');
    }

    private function authorizeReview(Request $request, OrderItem $orderItem): void
    {
        abort_unless($orderItem->order->user_id === $request->user()->id, 403);
        abort_unless($orderItem->order->status === OrderStatus::Completed, 422, 'Ulasan hanya tersedia setelah pesanan selesai.');
        abort_unless($orderItem->product_id && $orderItem->product, 422, 'Produk ini sudah tidak tersedia untuk diulas.');
    }
}
