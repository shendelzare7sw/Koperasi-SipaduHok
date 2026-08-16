<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
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
            'completedRevenue' => Order::where('status', OrderStatus::Completed->value)->sum('total'),
            'recentOrders' => Order::with('buyer')->latest()->limit(8)->get(),
        ]);
    }
}
