<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductType extends Model
{
    use HasFactory;
    protected $table = 'product_types';
    protected $fillable=['name', 'description'];

    public function product(): HasMany
    {
        return $this->hasMany(Product::class, "product_type_id");
    }
    public function size(): HasMany
    {
        return $this->hasMany(Size::class, "product_type_id");
    }
}
