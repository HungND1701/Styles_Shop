<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImageColorProduct extends Model
{
    use HasFactory;
    
    protected $table = 'images_colors_product';
    protected $fillable = ['name', 'color_product_id', 'url'];

    public function colorProduct()
    {
        return $this->belongsTo(ColorProduct::class, 'color_product_id');
    }
}
