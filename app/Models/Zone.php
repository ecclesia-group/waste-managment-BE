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
        return "zone_slug";
    }

    /**
     * Zones can be assigned to multiple providers (admin-configurable).
     */
    public function providers()
    {
        return $this->belongsToMany(
            Provider::class,
            'provider_zone_assignments',
            'zone_slug',
            'provider_slug'
        )
            ->withPivot(['assigned_at', 'status'])
            ->withTimestamps();
    }
}
