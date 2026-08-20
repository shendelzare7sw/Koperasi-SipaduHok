<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Payments\PaymentConfiguration;
use App\Services\Payments\PaywuzTransactionValidator;
use App\Services\PaywuzStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

class PaywuzWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        PaywuzStatusService $paywuz,
        PaymentConfiguration $payments,
        PaywuzTransactionValidator $transactionValidator,
    ): JsonResponse {
        $rawBody = $request->getContent();
        $payload = json_decode($rawBody, true);

        if (! is_array($payload)) {
            return response()->json(['message' => 'Invalid JSON payload.'], 400);
        }

        $validator = Validator::make($payload, [
            'event' => ['required', 'string', 'in:transaction.settlement,transaction.paid,transaction.failed,transaction.cancelled'],
            'data' => ['required', 'array'],
            'data.id' => ['required', 'string', 'max:100'],
            'data.orderId' => ['required', 'string', 'max:64'],
            'data.amount' => ['required', 'integer', 'min:1'],
            'data.status' => ['required', 'string', 'in:pending,settlement,success,failed,cancelled,expired'],
            'data.paymentMethod' => ['nullable', 'string', 'max:50'],
            'data.totalPayment' => ['nullable', 'integer', 'min:1'],
            'timestamp' => ['required', 'string', 'max:50'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid webhook payload.', 'errors' => $validator->errors()], 400);
        }

        $signature = (string) $request->header('X-Paywuz-Signature');
        $deliveryId = (string) $request->header('X-Paywuz-Delivery');
        $headerEvent = (string) $request->header('X-Paywuz-Event');

        if (! preg_match('/^sha256=[a-f0-9]{64}$/i', $signature) || blank($deliveryId) || strlen($deliveryId) > 100) {
            return response()->json(['message' => 'Missing or invalid Paywuz headers.'], 400);
        }

        if ($headerEvent !== '' && ! hash_equals($payload['event'], $headerEvent)) {
            return response()->json(['message' => 'Webhook event header does not match payload.'], 400);
        }

        $eventMatchesStatus = match ($payload['event']) {
            'transaction.settlement' => data_get($payload, 'data.status') === 'settlement',
            'transaction.paid' => data_get($payload, 'data.status') === 'success',
            'transaction.failed' => in_array(data_get($payload, 'data.status'), ['failed', 'expired'], true),
            'transaction.cancelled' => data_get($payload, 'data.status') === 'cancelled',
            default => false,
        };

        if (! $eventMatchesStatus) {
            return response()->json(['message' => 'Webhook event does not match transaction status.'], 400);
        }

        $order = Order::query()
            ->where('payment_gateway', 'paywuz')
            ->where('invoice_number', data_get($payload, 'data.orderId'))
            ->first();

        $keys = $order
            ? array_filter([$payments->apiKey($order->payment_environment)])
            : $payments->apiKeys();

        $signatureIsValid = collect($keys)->contains(function (string $apiKey) use ($rawBody, $signature): bool {
            $expected = 'sha256='.hash_hmac('sha256', $rawBody, $apiKey);

            return hash_equals($expected, $signature);
        });

        if (! $signatureIsValid) {
            return response()->json(['message' => 'Invalid webhook signature.'], 403);
        }

        if (! $order) {
            return response()->json(['message' => 'Webhook accepted; order not found.'], 202);
        }

        if (! $transactionValidator->amountMatches($order, $payload['data'])) {
            return response()->json(['message' => 'Webhook amount does not match the order.'], 422);
        }

        if (filled($order->payment_reference)
            && ! hash_equals((string) $order->payment_reference, (string) data_get($payload, 'data.id'))) {
            return response()->json(['message' => 'Webhook transaction reference does not match the order.'], 422);
        }

        $inserted = DB::table('payment_webhook_deliveries')->insertOrIgnore([
            'provider' => 'paywuz',
            'delivery_id' => $deliveryId,
            'event' => $payload['event'],
            'order_id' => $order->id,
            'payload_hash' => hash('sha256', $rawBody),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($inserted === 0) {
            return response()->json(['message' => 'Webhook already processed.']);
        }

        try {
            $paywuz->applyStatus($order, (string) data_get($payload, 'data.status'), $payload['data']);
            DB::table('payment_webhook_deliveries')
                ->where('delivery_id', $deliveryId)
                ->update(['processed_at' => now(), 'updated_at' => now()]);
        } catch (Throwable $exception) {
            DB::table('payment_webhook_deliveries')->where('delivery_id', $deliveryId)->delete();
            throw $exception;
        }

        return response()->json(['message' => 'Webhook processed.']);
    }
}
