<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'total_price',
        'status',
        'fullname',
        'address', 
        'email', 
        'phone_number',
        'city',
        'district',
        'commune',
        'note',
        'payment_method_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id')->select(['id', 'name']);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id')->select(['id','name']);
    }
    
    public function products()
    {
        return 
        $this->belongsToMany(Product::class)
            ->withPivot(['quantity', 'color_id', 'size_id', 'new_price', 'old_price'])
            ->withTimestamps()
            ->select(['products.id', 'products.name']); ;
    }
    public function statuses()
    {
        return $this->belongsToMany(Status::class)->withPivot('created_at')->orderBy('pivot_created_at', 'desc');;
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
    
}
