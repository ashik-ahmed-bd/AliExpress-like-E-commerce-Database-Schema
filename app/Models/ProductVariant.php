<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'sku', 'variant_name',
        'price_adjustment', 'quantity', 'image', 'is_active'
    ];

    protected $casts = [
        'price_adjustment' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Calculate the final price of this variant
    public function getFinalPriceAttribute()
    {
        return $this->product->actual_price + $this->price_adjustment;
    }
}
