<?php

namespace App\Providers;

use App\Contracts\PaymentGateway;
use App\Services\Payments\MidtransPaymentGateway;
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
            return config('services.payment_gateway') === 'midtrans'
                ? $app->make(MidtransPaymentGateway::class)
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
