<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Size extends Model
{
    use HasFactory;

    protected $fillable=['name', 'weight', 'height', 'product_type_id'];

    public function type(): BelongsTo
    {
        return $this->belongsTo(ProductType::class);
    }
}
