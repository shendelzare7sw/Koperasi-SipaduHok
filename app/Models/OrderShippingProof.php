<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderShippingProof extends Model
{
    use HasFactory;

    public const STAGE_DISPATCH = 'dispatch';

    public const STAGE_DELIVERY = 'delivery';

    protected $fillable = ['order_id', 'uploaded_by', 'stage', 'path', 'note', 'sort_order'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
