<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreSetting extends Model
{
    public const DEFAULTS = [
        'legal_name' => 'Koperasi Sipaduhok',
        'support_email' => 'koperasi@sipaduhok.id',
        'phone' => '',
        'whatsapp' => '',
        'address' => 'Alamat sekolah belum diatur.',
        'operating_hours' => 'Senin–Jumat pada jam operasional sekolah',
        'description' => 'Koperasi sekolah yang menyediakan buku, alat tulis, dan atribut sekolah.',
    ];

    protected $fillable = ['key', 'value'];

    /** @return array<string, string> */
    public static function values(): array
    {
        return [...self::DEFAULTS, ...self::query()->pluck('value', 'key')->all()];
    }
}
