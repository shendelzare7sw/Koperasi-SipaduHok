<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Buyer = 'pembeli';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin Koperasi',
            self::Buyer => 'Pembeli',
        };
    }
}
