<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalesReportController extends Controller
{
    public function __invoke(Request $request): View
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $start = Carbon::parse($validated['start_date'] ?? now()->startOfMonth()->toDateString())->startOfDay();
        $end = Carbon::parse($validated['end_date'] ?? now()->toDateString())->endOfDay();

        $baseQuery = Order::query()
            ->where('status', OrderStatus::Completed->value)
            ->whereBetween('completed_at', [$start, $end]);

        $orderIds = (clone $baseQuery)->select('id');
        $orderCount = (clone $baseQuery)->count();
        $revenue = (clone $baseQuery)->sum('total');
        $shippingRevenue = (clone $baseQuery)->sum('shipping_cost');
        $itemsSold = OrderItem::query()->whereIn('order_id', $orderIds)->sum('quantity');

        $topProducts = OrderItem::query()
            ->selectRaw('product_name, SUM(quantity) as total_quantity, SUM(subtotal) as total_sales')
            ->whereIn('order_id', (clone $baseQuery)->select('id'))
            ->groupBy('product_name')
            ->orderByDesc('total_quantity')
            ->limit(10)
            ->get();

        $orders = (clone $baseQuery)->with('buyer')->latest('completed_at')->paginate(15)->withQueryString();

        return view('admin.reports.sales', compact(
            'start', 'end', 'orderCount', 'revenue', 'shippingRevenue', 'itemsSold', 'topProducts', 'orders',
        ));
    }
}
