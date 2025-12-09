<?php
namespace App\Models;

class DistrictAssembly extends Actor
{
    protected $fillable = [
        'district_assembly_slug',
        'region',
        'district',
        'email',
        'phone_number',
        'password',
        'gps_address',
        'first_name',
        'last_name',
        'status',
        'profile_image',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
        'profile_image' => 'array',
        "password"      => "hashed",
    ];

    public function getRouteKeyName(): string
    {
        return "district_assembly_slug";
    }
}
