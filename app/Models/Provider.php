<?php
namespace App\Models;

class Provider extends Actor
{
    protected $fillable = [
        'provider_slug',
        'first_name',
        'last_name',
        'business_registration_number',
        'gps_address',
        'email',
        'phone_number',
        'password',
        'email_verified_at',
        'business_certificate_image',
        'mmda_contract_image',
        'tax_certificate_image',
        'epa_permit_image',
        'zone_id',
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
        "email_verified_at"          => "datetime",
        "password"                   => "hashed",
        "business_certificate_image" => "array",
        "mmda_contract_image"        => "array",
        "tax_certificate_image"      => "array",
        "epa_permit_image"           => "array",
        "profile_image"              => "array",
    ];
}
