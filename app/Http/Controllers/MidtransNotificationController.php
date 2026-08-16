<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\MidtransStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MidtransNotificationController extends Controller
{
    public function __invoke(Request $request, MidtransStatusService $midtrans): JsonResponse
    {
        if (blank(config('services.midtrans.server_key'))) {
            return response()->json(['message' => 'Midtrans is not configured.'], 503);
        }

        $payload = $request->validate([
            'order_id' => ['required', 'string'],
            'status_code' => ['required', 'string'],
            'gross_amount' => ['required'],
            'signature_key' => ['required', 'string'],
            'transaction_status' => ['required', 'string'],
            'fraud_status' => ['nullable', 'string'],
        ]);

        $expectedSignature = hash('sha512',
            $payload['order_id'].
            $payload['status_code'].
            $payload['gross_amount'].
            config('services.midtrans.server_key')
        );

        if (! hash_equals($expectedSignature, $payload['signature_key'])) {
            return response()->json(['message' => 'Invalid signature.'], 403);
        }

        $order = Order::query()
            ->where('payment_reference', $payload['order_id'])
            ->orWhere('invoice_number', $payload['order_id'])
            ->first();

        if (! $order) {
            return response()->json(['message' => 'Notification accepted; order not found.']);
        }

        if ((int) round((float) $payload['gross_amount']) !== $order->total) {
            return response()->json(['message' => 'Gross amount does not match the order.'], 422);
        }

        $midtrans->applyTransactionStatus(
            $order,
            $payload['transaction_status'],
            $payload['fraud_status'] ?? null,
        );

        return response()->json(['message' => 'Notification processed.']);
    }
}
