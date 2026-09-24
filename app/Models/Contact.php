<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'section',
        'order_num',
        'name',
        'email',
        'phone',
        'scope',
        'position',
        'region_expert',
        'notes',
    ];
}
