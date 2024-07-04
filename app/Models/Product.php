<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasFactory;

    protected $fillable=["name", "price", "sale", "tag_id", "product_type_id", "description"];

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, "categories_products");
    }

    public function tag()
    {
        return $this->belongsTo(Tag::class);
    }
    public function type(): BelongsTo
    {
        return $this->belongsTo(ProductType::class,'product_type_id');
    }

    public function colors(): BelongsToMany
    {
        return $this->belongsToMany(Color::class, "colors_products");
    }
    public function colorProduct()
    {
        return $this->hasMany(ColorProduct::class, 'product_id');
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class)->withPivot('quantity')->withTimestamps();
    }

    public function getTotalOrdersAttribute()
    {
        return $this->orders()->count();
    }

    public function getTotalQuantitySold()
    {
        return $this->orders()->sum('order_product.quantity');
    }
    
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    // public function getTotalQuantityAttribute()
    // {
    //     return $this->colorProduct()->sum('order_product.quantity');
    // }
}
