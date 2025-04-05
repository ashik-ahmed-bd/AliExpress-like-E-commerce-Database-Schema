<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'order_number', 'status', 'total_amount',
        'shipping_amount', 'tax_amount', 'payment_method', 'payment_status',
        'shipping_address'
    ];

    protected $casts = [
        'status' => 'string',
        'payment_status' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function shipments()
    {
        return $this->hasMany(OrderShipment::class);
    }
}
