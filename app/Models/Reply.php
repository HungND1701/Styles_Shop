<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reply extends Model
{
    use HasFactory;
    protected $fillable = ['review_id', 'content'];
    protected $table = 'reply';
    
    public function review()
    {
        return $this->belongsTo(Review::class);
    }

}
