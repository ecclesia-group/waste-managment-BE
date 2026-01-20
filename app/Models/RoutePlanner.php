<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoutePlanner extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'provider_slug',
        'driver_slug',
        'fleet_slug',
        'group_slug',
        'status',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Provider::class, 'provider_slug', 'provider_slug');
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_slug', 'driver_slug');
    }

    public function fleet()
    {
        return $this->belongsTo(Fleet::class, 'fleet_slug', 'fleet_slug');
    }

    public function group()
    {
        return $this->belongsTo(Group::class, 'group_slug', 'group_slug');
    }
}
