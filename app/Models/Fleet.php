<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fleet extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'fleet_slug',
        'vehicle_make',
        'model',
        'manufacture_year',
        'license_plate',
        'bin_capacity',
        'color',
        'owner_first_name',
        'owner_last_name',
        'owner_phone_number',
        'owner_address',
        'provider_slug',
        'insurance_expiry_date',
        'insurance_policy_number',
        'vehicle_images',
        'vehicle_registration_certificate_image',
        'vehicle_insurance_certificate_image',
        'vehicle_roadworthy_certificate_image',
        'status',
    ];

    protected $casts = [
        'vehicle_images'                         => 'array',
        'vehicle_registration_certificate_image' => 'array',
        'vehicle_insurance_certificate_image'    => 'array',
        'vehicle_roadworthy_certificate_image'   => 'array',
        'insurance_expiry_date'                  => 'datetime',
        'deleted_at'                             => 'datetime',
        'created_at'                             => 'datetime',
        'updated_at'                             => 'datetime',
    ];

    public function provider()
    {
        return $this->belongsTo(Provider::class, 'provider_slug', 'provider_slug');
    }
}
