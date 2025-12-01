<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DistrictAssembly extends Model
{
     protected $fillable = [
        'district_assembly_slug',
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
