<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'productCount' => Product::count(),
            'lowStockCount' => Product::where('stock', '<=', 5)->count(),
            'incomingOrderCount' => Order::whereIn('status', [
                OrderStatus::PendingPayment->value,
                OrderStatus::Processing->value,
            ])->count(),
            'pendingPaymentCount' => Order::where('status', OrderStatus::PendingPayment->value)->count(),
            'deliveryCount' => Order::whereIn('status', [
                OrderStatus::Ready->value,
                OrderStatus::OutForDelivery->value,
                OrderStatus::Delivered->value,
            ])->count(),
            'buyerCount' => User::where('role', UserRole::Buyer->value)->count(),
            'completedRevenue' => Order::where('status', OrderStatus::Completed->value)->sum('total'),
            'recentOrders' => Order::with('buyer')->latest()->limit(8)->get(),
        ]);
    }
}
