<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id', 'category_id', 'name', 'description',
        'price', 'discount_price', 'quantity',
        'featured_image', 'is_active'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'rating' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Auto-generate slug from name
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($product) {
            $product->slug = Str::slug($product->name);
        });
    }

    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function attributes()
    {
        return $this->hasMany(ProductAttribute::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    // Get actual price (discount price if available, otherwise regular price)
    public function getActualPriceAttribute()
    {
        return $this->discount_price ?? $this->price;
    }

    // Calculate the discount percentage
    public function getDiscountPercentageAttribute()
    {
        if (!$this->discount_price) {
            return 0;
        }

        return round((($this->price - $this->discount_price) / $this->price) * 100);
    }
}
