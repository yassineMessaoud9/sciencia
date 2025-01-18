<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    protected $table    = "orders";
    protected $fillable = [
        'order_serial_no',
        'user_id',
        'delivery_zone_id',
        'tax',
        'discount',
        'subtotal',
        'total',
        'delivery_charge',
        'order_type',
        'order_datetime',
        'payment_method',
        'pos_payment_method',
        'pos_payment_note',
        'payment_status',
        'status',
        'reason',
        'edited_amount',
        'edited_date',
        'source',
        'delivery_boy_id'
    ];

    protected $casts = [
        'id'                 => 'integer',
        'order_serial_no'    => 'string',
        'user_id'            => 'integer',
        'delivery_zone_id'   => 'integer',
        'tax'                => 'decimal:6',
        'discount'           => 'decimal:6',
        'subtotal'           => 'decimal:6',
        'total'              => 'decimal:6',
        'delivery_charge'    => 'decimal:6',
        'order_type'         => 'integer',
        'order_datetime'     => 'datetime',
        'payment_method'     => 'integer',
        'pos_payment_method' => 'integer',
        'pos_payment_note'   => 'string',
        'payment_status'     => 'integer',
        'status'             => 'integer',
        'reason'             => 'string',
        'edited_amount'      => 'double',
        'edited_date'        => 'datetime',
        'source'             => 'integer',
        'delivery_boy_id'    => 'integer'
    ];

    public function transaction(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Transaction::class);
    }

    public function orderProducts(): \Illuminate\Database\Eloquent\Relations\morphMany
    {
        return $this->morphMany(Stock::class, 'model');
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function address(): \Illuminate\Database\Eloquent\Relations\hasOne
    {
        return $this->hasOne(OrderAddress::class);
    }

    public function outletAddress(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(OrderOutletAddress::class);
    }

    public function paymentMethod(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class, 'payment_method');
    }

    public function deliveryBoy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'delivery_boy_id', 'id');
    }

    public function scopePending($query)
    {
        return $query->where('status', OrderStatus::PENDING);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', OrderStatus::CONFIRMED);
    }

    public function scopeOngoing($query)
    {
        return $query->where('status', OrderStatus::ON_THE_WAY);
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', OrderStatus::DELIVERED);
    }

    public function scopeCanceled($query)
    {
        return $query->where('status', OrderStatus::CANCELED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', OrderStatus::REJECTED);
    }
    public function scopeReturned($query)
    {
        return $query->where('status', OrderStatus::RETURNED);
    }
    public function deliveryZone(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class);
    }
}
