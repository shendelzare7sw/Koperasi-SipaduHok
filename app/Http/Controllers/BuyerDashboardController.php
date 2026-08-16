<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BuyerDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $orders = Order::query()->where('user_id', $request->user()->id);

        return view('buyer.dashboard', [
            'cartItemCount' => $request->user()->cartItems()->sum('quantity'),
            'wishlistCount' => $request->user()->wishlists()->count(),
            'addressCount' => $request->user()->addresses()->count(),
            'unreadNotificationCount' => $request->user()->unreadNotifications()->count(),
            'pendingPaymentCount' => (clone $orders)->where('status', OrderStatus::PendingPayment->value)->count(),
            'activeOrderCount' => (clone $orders)->whereIn('status', [
                OrderStatus::Processing->value,
                OrderStatus::Ready->value,
                OrderStatus::OutForDelivery->value,
                OrderStatus::Delivered->value,
            ])->count(),
            'completedOrderCount' => (clone $orders)->where('status', OrderStatus::Completed->value)->count(),
            'completedSpend' => (clone $orders)->where('status', OrderStatus::Completed->value)->sum('total'),
            'recentOrders' => (clone $orders)->with('items')->latest()->limit(5)->get(),
            'recommendedProducts' => Product::query()->with('images')->withCount('reviews')->withAvg('reviews', 'rating')->where('is_active', true)->where('stock', '>', 0)->latest()->limit(4)->get(),
            'wishlistIds' => $request->user()->wishlists()->pluck('product_id')->all(),
            'categories' => Product::CATEGORIES,
        ]);
    }
}
