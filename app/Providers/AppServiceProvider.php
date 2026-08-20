<?php

namespace App\Providers;

use App\Contracts\PaymentGateway;
use App\Services\Payments\PaymentConfiguration;
use App\Services\Payments\PaywuzPaymentGateway;
use App\Services\Payments\PlaceholderPaymentGateway;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentGateway::class, function ($app) {
            return $app->make(PaymentConfiguration::class)->gateway() === 'paywuz'
                ? $app->make(PaywuzPaymentGateway::class)
                : $app->make(PlaceholderPaymentGateway::class);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
