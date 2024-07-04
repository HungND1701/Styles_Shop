<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BannerHomepage extends Model
{
    use HasFactory;
    protected $table='banner_homepage';
    protected $fillable = ['stt','url', 'is_active'];
}
