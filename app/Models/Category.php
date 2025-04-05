<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id', 'name', 'description',
        'image', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Auto-generate slug from name
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($category) {
            $category->slug = Str::slug($category->name);
        });
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    // Get all ancestors of a category
    public function ancestors()
    {
        $ancestors = collect([]);
        $category = $this->parent;

        while (!is_null($category)) {
            $ancestors->push($category);
            $category = $category->parent;
        }

        return $ancestors->reverse();
    }
}
