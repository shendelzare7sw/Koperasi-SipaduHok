<?php

namespace App\Services\Payments;

use App\Models\PaymentSetting;
use Throwable;

class PaymentConfiguration
{
    private bool $loaded = false;

    private ?PaymentSetting $setting = null;

    public function gateway(): string
    {
        $setting = $this->setting();

        if ($setting) {
            return $setting->is_active ? 'paywuz' : 'placeholder';
        }

        return (string) config('services.payment_gateway', 'placeholder');
    }

    public function environment(): string
    {
        return $this->isProduction() ? 'production' : 'sandbox';
    }

    public function apiKey(?string $environment = null): ?string
    {
        $environment ??= $this->environment();

        return $environment === 'production'
            ? ($this->databaseValue('production_api_key') ?: config('services.paywuz.production_api_key'))
            : ($this->databaseValue('sandbox_api_key') ?: config('services.paywuz.sandbox_api_key'));
    }

    /** @return list<string> */
    public function apiKeys(): array
    {
        return collect([$this->apiKey('sandbox'), $this->apiKey('production')])
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function isProduction(): bool
    {
        return $this->setting()?->is_production
            ?? config('services.paywuz.environment', 'sandbox') === 'production';
    }

    public function isPaywuzEnabled(): bool
    {
        return $this->gateway() === 'paywuz';
    }

    public function isReady(): bool
    {
        return $this->isPaywuzEnabled() && filled($this->apiKey());
    }

    public function isCheckoutReady(): bool
    {
        return $this->gateway() === 'placeholder'
            ? app()->environment('local', 'testing')
            : $this->isReady();
    }

    public function baseUrl(): string
    {
        return rtrim((string) config('services.paywuz.base_url', 'https://api.paywuz.id/v1'), '/');
    }

    public function expiryMinutes(): int
    {
        return max(5, min((int) config('services.paywuz.expiry_minutes', 720), 10080));
    }

    /** @return array<string, bool|string|null> */
    public function status(): array
    {
        $setting = $this->setting();

        return [
            'gateway' => $this->gateway(),
            'enabled' => $this->isPaywuzEnabled(),
            'ready' => $this->isReady(),
            'checkout_ready' => $this->isCheckoutReady(),
            'environment' => $this->environment(),
            'api_key_configured' => filled($this->apiKey()),
            'sandbox_api_key_configured' => filled($this->apiKey('sandbox')),
            'production_api_key_configured' => filled($this->apiKey('production')),
            'source' => $setting ? 'panel_admin' : 'environment',
        ];
    }

    public function reset(): void
    {
        $this->loaded = false;
        $this->setting = null;
    }

    private function setting(): ?PaymentSetting
    {
        if ($this->loaded) {
            return $this->setting;
        }

        $this->loaded = true;

        try {
            $this->setting = PaymentSetting::query()->where('provider', 'paywuz')->first();
        } catch (Throwable) {
            $this->setting = null;
        }

        return $this->setting;
    }

    private function databaseValue(string $key): mixed
    {
        try {
            return $this->setting()?->{$key};
        } catch (Throwable) {
            return null;
        }
    }
}
