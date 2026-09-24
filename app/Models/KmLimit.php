<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KmLimit extends Model
{
    protected $fillable = ['scope', 'monthly_km', 'notes'];

    protected $casts = ['monthly_km' => 'float'];
}
