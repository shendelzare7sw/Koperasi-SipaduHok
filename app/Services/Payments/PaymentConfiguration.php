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
            return $setting->is_active ? 'midtrans' : 'placeholder';
        }

        return (string) config('services.payment_gateway', 'placeholder');
    }

    public function serverKey(): ?string
    {
        return $this->databaseValue('server_key') ?: config('services.midtrans.server_key');
    }

    public function clientKey(): ?string
    {
        return $this->databaseValue('client_key') ?: config('services.midtrans.client_key');
    }

    public function merchantId(): ?string
    {
        return $this->databaseValue('merchant_id') ?: config('services.midtrans.merchant_id');
    }

    public function isProduction(): bool
    {
        return $this->setting()?->is_production
            ?? (bool) config('services.midtrans.is_production', false);
    }

    public function isMidtransEnabled(): bool
    {
        return $this->gateway() === 'midtrans';
    }

    public function isReady(): bool
    {
        return $this->isMidtransEnabled()
            && filled($this->serverKey())
            && filled($this->clientKey());
    }

    public function isCheckoutReady(): bool
    {
        return $this->gateway() === 'placeholder'
            ? app()->environment('local', 'testing')
            : $this->isReady();
    }

    public function snapScriptUrl(): string
    {
        return $this->isProduction()
            ? 'https://app.midtrans.com/snap/snap.js'
            : 'https://app.sandbox.midtrans.com/snap/snap.js';
    }

    /** @return array<string, bool|string|null> */
    public function status(): array
    {
        $setting = $this->setting();

        return [
            'gateway' => $this->gateway(),
            'enabled' => $this->isMidtransEnabled(),
            'ready' => $this->isReady(),
            'checkout_ready' => $this->isCheckoutReady(),
            'environment' => $this->isProduction() ? 'production' : 'sandbox',
            'server_key_configured' => filled($this->serverKey()),
            'client_key_configured' => filled($this->clientKey()),
            'merchant_id' => $this->merchantId(),
            'source' => $setting ? 'panel_admin' : 'environment',
            'database_server_key' => $setting ? filled($this->databaseValue('server_key')) : false,
            'database_client_key' => $setting ? filled($this->databaseValue('client_key')) : false,
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
            $this->setting = PaymentSetting::query()->where('provider', 'midtrans')->first();
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
