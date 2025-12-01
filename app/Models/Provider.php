<?php
namespace App\Models;

class Provider extends Actor
{
    protected $fillable = [
        'provider_slug',
        'first_name',
        'last_name',
        'business_name',
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
        "email_verified_at" => "datetime",
        "password"          => "hashed",
    ];

    public function getRouteKeyName(): string
    {
        return "provider_slug";
    }

    public function zones()
    {
        return $this->hasMany(Zone::class, 'partner_slug', 'partner_slug');
    }
}
