<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Models\Order;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class PaywuzPaymentGateway implements PaymentGateway
{
    public function __construct(
        private readonly PaymentConfiguration $configuration,
        private readonly PaywuzTransactionValidator $validator,
    ) {}

    public function paymentMethods(): array
    {
        $apiKey = $this->requiredApiKey();
        $cacheKey = 'paywuz:payment-methods:'.sha1($apiKey);

        return Cache::remember($cacheKey, now()->addMinutes(5), function (): array {
            $data = $this->data($this->client()->get('/payment-methods'));

            if (! is_array($data)) {
                throw new RuntimeException('Respons metode pembayaran Paywuz tidak valid.');
            }

            $methods = collect($data)->map(function (mixed $method): ?array {
                if (! is_array($method) || blank($method['code'] ?? null) || blank($method['name'] ?? null)) {
                    return null;
                }

                return [
                    'code' => (string) $method['code'],
                    'name' => (string) $method['name'],
                    'type' => (string) ($method['type'] ?? 'unknown'),
                    'fee_flat' => max(0, (int) data_get($method, 'fee.flatIdr', 0)),
                    'fee_percent_bps' => max(0, (int) data_get($method, 'fee.percentBps', 0)),
                    'min_amount' => max(1, (int) data_get($method, 'limits.minIdr', 1)),
                    'max_amount' => max(1, (int) data_get($method, 'limits.maxIdr', PHP_INT_MAX)),
                ];
            })->filter();

            if ($methods->contains(fn (array $method) => $method['code'] === 'VA' && $method['type'] === 'meta')) {
                $methods = $methods->reject(fn (array $method) => $method['type'] === 'virtual_account');
            }

            return $methods->values()->all();
        });
    }

    public function createTransaction(Order $order, string $paymentMethod): array
    {
        $data = $this->data($this->client($order->payment_environment)->post('/transactions', [
            'orderId' => $order->invoice_number,
            'amount' => $order->total,
            'paymentMethod' => $paymentMethod,
            'expiryMinutes' => $this->configuration->expiryMinutes(),
            'redirectUrl' => route('orders.show', $order),
            'metadata' => [
                'order_id' => $order->id,
                'buyer_id' => $order->user_id,
            ],
        ]));

        if (! is_array($data)
            || blank($data['id'] ?? null)
            || blank($data['status'] ?? null)
            || ! hash_equals((string) $order->invoice_number, (string) ($data['orderId'] ?? ''))
            || ! $this->validator->amountMatches($order, $data)) {
            throw new RuntimeException('Respons pembuatan transaksi Paywuz tidak lengkap.');
        }

        $paymentUrl = filled($data['paymentUrl'] ?? null) ? (string) $data['paymentUrl'] : null;
        if ($paymentUrl && ! $this->isTrustedPaymentUrl($paymentUrl)) {
            throw new RuntimeException('URL pembayaran Paywuz tidak valid.');
        }

        return [
            'reference' => (string) $data['id'],
            'status' => $this->internalStatus((string) $data['status']),
            'payment_url' => $paymentUrl,
            'total_payment' => max($order->total, (int) ($data['totalPayment'] ?? $order->total)),
            'expires_at' => filled($data['expiresAt'] ?? null) ? (string) $data['expiresAt'] : null,
        ];
    }

    public function fetchTransaction(Order $order): array
    {
        $data = $this->data($this->client($order->payment_environment)->get(
            '/transactions/'.rawurlencode((string) $order->invoice_number),
        ));

        if (! is_array($data)
            || blank($data['status'] ?? null)
            || ! hash_equals((string) $order->invoice_number, (string) ($data['orderId'] ?? ''))
            || ! $this->validator->amountMatches($order, $data)
            || (filled($order->payment_reference)
                && ! hash_equals((string) $order->payment_reference, (string) ($data['id'] ?? '')))) {
            throw new RuntimeException('Respons status transaksi Paywuz tidak lengkap.');
        }

        return $data;
    }

    public function cancelTransaction(Order $order): array
    {
        $data = $this->data($this->client($order->payment_environment)->post(
            '/transactions/'.rawurlencode((string) $order->invoice_number).'/cancel',
        ));

        if (! is_array($data)
            || blank($data['status'] ?? null)
            || ! hash_equals((string) $order->invoice_number, (string) ($data['orderId'] ?? ''))
            || (filled($order->payment_reference)
                && ! hash_equals((string) $order->payment_reference, (string) ($data['id'] ?? '')))) {
            throw new RuntimeException('Respons pembatalan transaksi Paywuz tidak lengkap.');
        }

        return $data;
    }

    private function client(?string $environment = null): PendingRequest
    {
        return Http::baseUrl($this->configuration->baseUrl())
            ->withToken($this->requiredApiKey($environment))
            ->acceptJson()
            ->asJson()
            ->connectTimeout(5)
            ->timeout(15)
            ->retry(2, 300, throw: false);
    }

    private function requiredApiKey(?string $environment = null): string
    {
        $apiKey = $this->configuration->apiKey($environment);

        if (blank($apiKey)) {
            throw new RuntimeException('API key Paywuz belum dikonfigurasi.');
        }

        return $apiKey;
    }

    private function data(Response $response): mixed
    {
        if (! $response->successful()) {
            $message = $response->json('message') ?: 'Permintaan ke Paywuz gagal.';
            throw new RuntimeException(Str::limit((string) $message, 300));
        }

        return $response->json('data');
    }

    private function internalStatus(string $status): string
    {
        return match ($status) {
            'failed', 'cancelled' => 'failed',
            'expired' => 'expired',
            // Fulfilment is intentionally never triggered from a create response.
            // A paid order is processed only through a verified webhook or status sync.
            default => 'pending',
        };
    }

    private function isTrustedPaymentUrl(string $url): bool
    {
        $parts = parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));

        return ($parts['scheme'] ?? null) === 'https'
            && ($host === 'paywuz.id'
                || str_ends_with($host, '.paywuz.id')
                || $host === 'paywuz.com'
                || str_ends_with($host, '.paywuz.com'));
    }
}
