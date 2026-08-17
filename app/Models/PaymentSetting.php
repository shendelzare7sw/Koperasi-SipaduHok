<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentSetting extends Model
{
    protected $fillable = [
        'provider',
        'is_active',
        'is_production',
        'server_key',
        'client_key',
        'merchant_id',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_production' => 'boolean',
            'server_key' => 'encrypted',
            'client_key' => 'encrypted',
        ];
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
