<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdentityVerification extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'legal_name',
        'nik',
        'nik_hash',
        'document_path',
        'document_mime',
        'status',
        'review_note',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
    ];

    protected $hidden = ['nik', 'nik_hash', 'document_path'];

    protected function casts(): array
    {
        return [
            'legal_name' => 'encrypted',
            'nik' => 'encrypted',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_VERIFIED => 'Terverifikasi',
            self::STATUS_REJECTED => 'Ditolak',
            default => 'Menunggu Verifikasi',
        };
    }

    public function maskedNik(): string
    {
        return str_repeat('•', 12).substr($this->nik, -4);
    }
}
