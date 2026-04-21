<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Suburb extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'suburb_slug',
        'district_assembly_slug',
        'name',
        'description',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'suburb_slug';
    }

    public function districtAssembly()
    {
        return $this->belongsTo(DistrictAssembly::class, 'district_assembly_slug', 'district_assembly_slug');
    }

    public function zones()
    {
        return $this->belongsToMany(
            Zone::class,
            'suburb_zone',
            'suburb_id',
            'zone_slug',
            'id',
            'zone_slug'
        )->withTimestamps();
    }
}
