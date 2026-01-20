<?php
namespace App\Models;

class Provider extends Actor
{
    protected $fillable = [
        'provider_slug',
        'first_name',
        'last_name',
        'business_name',
        'district_assembly',
        'business_registration_number',
        'gps_address',
        'email',
        'phone_number',
        'password',
        'email_verified_at',
        'business_certificate_image',
        'district_assembly_contract_image',
        'tax_certificate_image',
        'epa_permit_image',
        'zone_slug',
        'status',
        'region',
        'location',
        'profile_image',
    ];

    protected $hidden = [
        "password",
        "created_at",
        "updated_at",
    ];

    protected $casts = [
        "email_verified_at"                => "datetime",
        "password"                         => "hashed",
        'deleted_at'                       => 'datetime',
        'created_at'                       => 'datetime',
        'updated_at'                       => 'datetime',
        "business_certificate_image"       => "array",
        "district_assembly_contract_image" => "array",
        "tax_certificate_image"            => "array",
        "epa_permit_image"                 => "array",
        "profile_image"                    => "array",
    ];

    public function getRouteKeyName(): string
    {
        return "provider_slug";
    }

    public function zones()
    {
        return $this->hasMany(Zone::class, 'zone_slug', 'zone_slug');
    }

    public function groups()
    {
        return $this->hasMany(Group::class, 'provider_slug', 'provider_slug');
    }

    public function routes()
    {
        return $this->hasMany(RoutePlanner::class, 'provider_slug', 'provider_slug');
    }

    public function fleets()
    {
        return $this->hasMany(Fleet::class, 'provider_slug', 'provider_slug');
    }

    public function drivers()
    {
        return $this->hasMany(Driver::class, 'provider_slug', 'provider_slug');
    }

    public function customers()
    {
        return $this->hasMany(Client::class, 'provider_slug', 'provider_slug');
    }
}
