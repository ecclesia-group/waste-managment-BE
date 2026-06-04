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
        'zone_slug',
        'region',
        'description',
        'locations',
        'status',
    ];

    protected $casts = [
        'locations'  => 'array',
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'zone_slug';
    }

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

    public function facilities()
    {
        return $this->belongsToMany(
            Facility::class,
            'facility_zones',
            'zone_slug',
            'facility_slug',
            'zone_slug',
            'facility_slug'
        )
            ->withPivot(['assigned_at', 'status'])
            ->withTimestamps();
    }

    public function districtAssemblies()
    {
        return $this->belongsToMany(
            DistrictAssembly::class,
            'district_assembly_zones',
            'zone_slug',
            'district_assembly_slug',
            'zone_slug',
            'district_assembly_slug'
        )
            ->withPivot(['assigned_at', 'status'])
            ->withTimestamps();
    }
}
