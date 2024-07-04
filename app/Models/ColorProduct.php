<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ColorProduct extends Model
{
    use HasFactory;

    protected $table = 'colors_products';

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function color()
    {
        return $this->belongsTo(Color::class, 'color_id');
    }

    public function images()
    {
        return $this->hasMany(ImageColorProduct::class, 'color_product_id');
    }

    public function sizes()
    {
        return $this->hasMany(SizeColorProduct::class, 'color_product_id');
    }
}
