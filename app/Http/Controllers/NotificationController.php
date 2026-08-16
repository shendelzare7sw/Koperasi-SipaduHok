<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()->notifications()
            ->when($request->string('filter')->value() === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('notifications.index', compact('notifications'));
    }

    public function open(Request $request, DatabaseNotification $notification): RedirectResponse
    {
        $this->authorizeNotification($request, $notification);
        $notification->markAsRead();

        $order = Order::find($notification->data['order_id'] ?? null);

        if (! $order) {
            return redirect()->route('notifications.index')->with('success', 'Notifikasi ditandai sudah dibaca.');
        }

        if (! $request->user()->isAdmin() && $order->user_id !== $request->user()->id) {
            abort(403);
        }

        return redirect()->route(
            $request->user()->isAdmin() ? 'admin.orders.show' : 'orders.show',
            $order,
        );
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }

    private function authorizeNotification(Request $request, DatabaseNotification $notification): void
    {
        abort_unless(
            $notification->notifiable_type === $request->user()::class
            && (int) $notification->notifiable_id === $request->user()->id,
            403,
        );
    }
}
