<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TempImage extends Model
{
    use HasFactory;
    const UPDATED_AT = null;

    protected $fillable=['url', 'created_at'];
}
