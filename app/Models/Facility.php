<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Facility extends Model
{
    use HasFactory;

    protected $fillable = [
        'facility_slug',
        'region',
        'district',
        'name',
        'email',
        'phone_number',
        'password',
        'gps_address',
        'first_name',
        'last_name',
        'business_certificate_image',
        'mmda_contract_image',
        'tax_certificate_image',
        'epa_permit_image',
        'type',
        'ownership',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'created_at'                 => 'datetime',
        'updated_at'                 => 'datetime',
        'business_certificate_image' => 'array',
        'mmda_contract_image'        => 'array',
        'tax_certificate_image'      => 'array',
        'epa_permit_image'           => 'array',
        'profile_image'              => 'array',
    ];
}
