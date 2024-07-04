<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoryHomepage extends Model
{
    use HasFactory;

    protected $table='categories_homepage';

    protected $fillable = ['category_id', 'stt'];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }
}
