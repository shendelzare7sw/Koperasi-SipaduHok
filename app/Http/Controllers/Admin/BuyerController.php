<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BuyerController extends Controller
{
    public function index(Request $request): View
    {
        $buyers = User::query()
            ->where('role', UserRole::Buyer->value)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where(fn ($nested) => $nested
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%"));
            })
            ->withCount('orders')
            ->withSum([
                'orders as completed_spend' => fn ($query) => $query->where('status', OrderStatus::Completed->value),
            ], 'total')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.buyers.index', ['buyers' => $buyers]);
    }
}
