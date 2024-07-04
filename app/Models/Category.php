<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name','sub_title' ,'banner_img_url','description'];
    protected $appends = ['product_count'];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, "categories_products");
    }

    public function categoryHomepage()
    {
        return $this->hasOne(CategoryHomepage::class);
    }

    public function countProducts(): int
    {
        return $this->products()->count();
    }

    public function getProductCountAttribute(): int
    {
        return $this->countProducts();
    }
}
