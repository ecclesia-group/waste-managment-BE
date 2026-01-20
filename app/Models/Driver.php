<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Driver extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'driver_slug',
        'provider_slug',
        'first_name',
        'middle_name',
        'last_name',
        'date_of_birth',
        'id_card_type',
        'id_card_number',
        'license_class',
        'license_number',
        'license_date_issued',
        'license_expiry_issued',
        'email',
        'password',
        'phone_number',
        'address',
        'emergency_contact_name',
        'emergency_phone_number',
        'emergency_contract_address',
        'license_front_image',
        'license_back_image',
        'profile_image',
        'status',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'date_of_birth'         => 'date',
        'license_date_issued'   => 'date',
        'license_expiry_issued' => 'date',
        'created_at'            => 'datetime',
        'updated_at'            => 'datetime',
        'deleted_at'            => 'datetime',
        'profile_image'         => 'array',
        'license_back_image'    => 'array',
        'license_front_image'   => 'array',
        "password"              => "hashed",
    ];

    public function getRouteKeyName(): string
    {
        return "driver_slug";
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class, 'provider_slug', 'provider_slug');
    }
}
