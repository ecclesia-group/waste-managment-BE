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
        'district_assembly_slug',
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
        return "zone_slug";
    }

    /**
     * Zones can be assigned to multiple providers (admin-configurable).
     */
    public function districtAssembly()
    {
        return $this->belongsTo(DistrictAssembly::class, 'district_assembly_slug', 'district_assembly_slug');
    }

    public function suburbs()
    {
        return $this->belongsToMany(
            Suburb::class,
            'suburb_zone',
            'zone_slug',
            'suburb_id',
            'zone_slug',
            'id'
        )->withTimestamps();
    }

    public function providers()
    {
        return $this->belongsToMany(
            Provider::class,
            'provider_zone_assignments',
            'zone_slug', // FK on pivot -> zones.zone_slug
            'provider_slug', // FK on pivot -> providers.provider_slug
            'zone_slug', // zones parent key
            'provider_slug' // providers related key
        )
            ->withPivot(['assigned_at', 'status'])
            ->withTimestamps();
    }
}
