<?php
namespace App\Models;

class DistrictAssembly extends Actor
{
    protected $fillable = [
        'district_assembly_slug',
        'parent_slug',
        'is_main',
        'role_slug',
        'region',
        'district',
        'email',
        'phone_number',
        'password',
        'gps_address',
        'first_name',
        'last_name',
        'status',
        'suspension_reason',
        'corrective_action',
        'suspended_at',
        'profile_image',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
        'deleted_at'    => 'datetime',
        'suspended_at'  => 'datetime',
        'is_main'       => 'boolean',
        'profile_image' => 'array',
        "password"      => "hashed",
    ];

    public function getRouteKeyName(): string
    {
        return "district_assembly_slug";
    }

    /** Zones assigned to this MMDA via district_assembly_zones pivot. */
    public function zones()
    {
        return $this->belongsToMany(
            Zone::class,
            'district_assembly_zones',
            'district_assembly_slug',
            'zone_slug',
            'district_assembly_slug',
            'zone_slug'
        )->withPivot(['assigned_at', 'status']);
    }
}
