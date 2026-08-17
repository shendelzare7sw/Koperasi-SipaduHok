<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'label', 'recipient_name', 'phone', 'full_address',
        'village', 'district', 'city', 'province', 'postal_code', 'is_primary',
    ];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function regionLine(): string
    {
        return collect([$this->village, $this->district, $this->city, $this->province, $this->postal_code])
            ->filter(fn ($value) => filled($value))
            ->implode(', ');
    }

    public function formattedAddress(): string
    {
        return collect([$this->full_address, $this->regionLine()])
            ->filter(fn ($value) => filled($value))
            ->implode("\n");
    }
}
