<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImageReview extends Model
{
    use HasFactory;

    protected $fillable = ["url", "review_id"];

    protected $table='images_review';

    public function review()
    {
        return $this->belongsTo(Review::class, 'review_id');
    }

}
