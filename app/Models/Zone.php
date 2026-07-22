<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Zone extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'region',
        'description',
        'locations',
        'status',
        'district_assembly_slug',
        'admin_slug',
    ];

    protected $hidden = [
        'pivot',
    ];

    protected $casts = [
        'locations'  => 'array',
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function providers()
    {
        return $this->belongsToMany(
            Provider::class,
            'provider_zones',
            'zone_slug',
            'provider_slug',
            'zone_slug',
            'provider_slug'
        )
            ->withPivot(['assigned_at', 'status'])
            ->withTimestamps();
    }

    public function districtAssembly()
    {
        return $this->belongsTo(DistrictAssembly::class, 'district_assembly_slug', 'district_assembly_slug');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_slug', 'admin_slug');
    }
}
