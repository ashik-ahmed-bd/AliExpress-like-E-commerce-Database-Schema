<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderShipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'tracking_number', 'carrier', 'status', 'shipped_at', 'delivered_at', 'notes'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
