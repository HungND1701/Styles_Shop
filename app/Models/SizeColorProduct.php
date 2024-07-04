<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SizeColorProduct extends Model
{
    use HasFactory;

    protected $table = 'sizes_products';

    public function colorProduct()
    {
        return $this->belongsTo(ColorProduct::class, 'color_product_id');
    }
    public function size()
    {
        return $this->belongsTo(Size::class, 'size_id', 'id');
    }
}
