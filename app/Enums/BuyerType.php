<?php

namespace App\Enums;

enum BuyerType: string
{
    case Student = 'siswa';
    case Parent = 'orang_tua';

    public function label(): string
    {
        return match ($this) {
            self::Student => 'Siswa',
            self::Parent => 'Orang Tua/Wali',
        };
    }
}
