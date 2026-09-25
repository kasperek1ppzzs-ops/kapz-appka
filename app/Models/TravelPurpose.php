<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TravelPurpose extends Model
{
    protected $fillable = ['code', 'role', 'title', 'conclusion', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}
