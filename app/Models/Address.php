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
        'street', 'house_number', 'rt', 'rw', 'landmark',
        'village', 'district', 'city', 'province', 'postal_code',
        'province_code', 'city_code', 'district_code', 'village_code',
        'latitude', 'longitude', 'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
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

    public function mapsUrl(): ?string
    {
        if ($this->latitude === null || $this->longitude === null) {
            return null;
        }

        return 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($this->latitude.','.$this->longitude);
    }
}
