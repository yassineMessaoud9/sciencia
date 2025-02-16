<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderCoupon extends Model
{
    use HasFactory;

    protected $table = "order_coupons";
    protected $fillable = [
        'order_id',
        'coupon_id',
        'user_id',
        'discount'
    ];

    protected $casts = [
        'id'           => 'integer',
        'order_id'     => 'integer',
        'coupon_id' => 'integer',
        'user_id'      => 'integer',
        'discount' => 'decimal:6'
    ];

    public function coupon():BelongsTo
    {
        return $this->belongsTo(Coupon::class, 'coupon_id', 'id');
    }
}
