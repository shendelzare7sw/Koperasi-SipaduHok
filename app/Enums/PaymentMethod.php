<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Qris = 'qris';
    case VirtualAccount = 'virtual_account';

    public function label(): string
    {
        return match ($this) {
            self::Qris => 'QRIS',
            self::VirtualAccount => 'Virtual Account',
        };
    }
}
