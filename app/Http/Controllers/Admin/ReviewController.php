<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Services\OrderNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function reply(
        Request $request,
        Review $review,
        OrderNotificationService $notifications,
    ): RedirectResponse {
        $validated = $request->validate([
            'admin_reply' => ['required', 'string', 'max:1500'],
        ]);

        $review->update([
            'admin_reply' => $validated['admin_reply'],
            'admin_replied_at' => now(),
        ]);
        $review->loadMissing(['order', 'orderItem']);
        $notifications->reviewReplied($review->order, $review->orderItem->product_name);

        return back()->with('success', 'Balasan ulasan berhasil disimpan.');
    }
}
