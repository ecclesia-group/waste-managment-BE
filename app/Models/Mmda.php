<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mmda extends Model
{
    use HasFactory;

    protected $fillable = [
        'mmda_slug',
        'region',
        'district',
        'email',
        'password',
        'gps_address',
        'first_name',
        'last_name',
        'phone_number',
        'status',
        'profile_image',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'profile_image' => 'array',
    ];
}
