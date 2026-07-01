<?php
namespace App\Models;

class Provider extends Actor
{
    protected $fillable = [
        'provider_slug',
        'parent_slug',
        'is_main',
        'role_slug',
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
        'status',
        'suspension_reason',
        'corrective_action',
        'suspended_at',
        'region',
        'location',
        'profile_image',
        'admin_slug',
    ];

    protected $hidden = [
        'password',
        'created_at',
        'updated_at',
        'pivot',
    ];

    protected $casts = [
        "email_verified_at"                => "datetime",
        "password"                         => "hashed",
        'deleted_at'                       => 'datetime',
        'created_at'                       => 'datetime',
        'updated_at'                       => 'datetime',
        'suspended_at'                     => 'datetime',
        'is_main'                          => 'boolean',
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

    /**
     * Admin can assign multiple zones to a provider via provider_zones.
     */
    public function zones()
    {
        return $this->belongsToMany(
            Zone::class,
            'provider_zones',
            'provider_slug',
            'zone_id',
            'provider_slug',
            'id'
        )
            ->withPivot(['assigned_at', 'status'])
            ->wherePivot('status', 'active')
            ->withTimestamps();
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

    public function violations()
    {
        return $this->hasMany(Violation::class, 'provider_slug', 'provider_slug');
    }

    public function complaints()
    {
        return $this->hasMany(Complaint::class, 'provider_slug', 'provider_slug');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'provider_slug', 'provider_slug');
    }

    public function mmda()
    {
        return $this->belongsTo(DistrictAssembly::class, 'district_assembly', 'district_assembly_slug');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_slug', 'admin_slug');
    }

    public function fees()
    {
        return $this->hasMany(ProviderFee::class, 'provider_slug', 'provider_slug');
    }
}
