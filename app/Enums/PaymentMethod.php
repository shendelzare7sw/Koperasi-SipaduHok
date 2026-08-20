<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case PaymentGateway = 'payment_gateway';

    public function label(): string
    {
        return 'Payment Gateway';
    }
}
