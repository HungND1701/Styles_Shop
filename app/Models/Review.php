<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 
        'product_id', 
        'order_id', 
        'review', 
        'rating'
    ];

    public function user()
    {
        return $this->belongsTo(User::class)->select(['id', 'name']);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class)->select(['id', 'total_price', 'created_at']);
    }

    public function images()
    {
        return $this->hasMany(ImageReview::class);
    }
    public function replies()
    {
        return $this->hasMany(Reply::class);
    }

}
