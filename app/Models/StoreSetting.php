<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreSetting extends Model
{
    public const DEFAULTS = [
        'legal_name' => 'Toko Sipaduhok',
        'support_email' => 'toko@sipaduhok.id',
        'phone' => '',
        'whatsapp' => '',
        'address' => 'Alamat toko belum diatur.',
        'operating_hours' => 'Senin–Jumat pada jam operasional toko',
        'description' => 'Toko kebutuhan sekolah yang menyediakan buku, alat tulis, dan atribut sekolah.',
    ];

    protected $fillable = ['key', 'value'];

    /** @return array<string, string> */
    public static function values(): array
    {
        return [...self::DEFAULTS, ...self::query()->pluck('value', 'key')->all()];
    }
}
