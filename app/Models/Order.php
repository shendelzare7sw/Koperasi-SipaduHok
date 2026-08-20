<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number', 'user_id', 'buyer_name', 'student_name', 'class_name', 'phone',
        'courier_id', 'courier_name', 'shipping_cost', 'delivery_address',
        'delivery_latitude', 'delivery_longitude', 'delivery_maps_url',
        'status', 'payment_method', 'payment_gateway', 'payment_status', 'payment_reference',
        'payment_token', 'gateway_payment_method', 'payment_url', 'gateway_total',
        'payment_environment', 'payment_expires_at', 'gateway_settled_at',
        'subtotal', 'total', 'notes', 'paid_at', 'ready_at', 'dispatched_at', 'delivered_at',
        'received_confirmed_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_method' => PaymentMethod::class,
            'payment_status' => PaymentStatus::class,
            'shipping_cost' => 'integer',
            'delivery_latitude' => 'decimal:7',
            'delivery_longitude' => 'decimal:7',
            'subtotal' => 'integer',
            'total' => 'integer',
            'gateway_total' => 'integer',
            'payment_expires_at' => 'datetime',
            'gateway_settled_at' => 'datetime',
            'paid_at' => 'datetime',
            'ready_at' => 'datetime',
            'dispatched_at' => 'datetime',
            'delivered_at' => 'datetime',
            'received_confirmed_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->oldest();
    }

    public function shippingProofs(): HasMany
    {
        return $this->hasMany(OrderShippingProof::class)->orderBy('sort_order')->orderBy('id');
    }

    public function dispatchProofs(): HasMany
    {
        return $this->shippingProofs()->where('stage', OrderShippingProof::STAGE_DISPATCH);
    }

    public function deliveryProofs(): HasMany
    {
        return $this->shippingProofs()->where('stage', OrderShippingProof::STAGE_DELIVERY);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function statusLabel(): string
    {
        return $this->status->label();
    }

    public function canBeConfirmedByBuyer(): bool
    {
        return $this->status === OrderStatus::Delivered && $this->deliveryProofs()->exists();
    }
}
