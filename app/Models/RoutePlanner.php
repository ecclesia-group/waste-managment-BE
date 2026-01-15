<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoutePlanner extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'client_slug',
        'driver_slug',
        'fleet_slug',
        'zone_slug',
        'status',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_slug', 'client_slug');
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_slug', 'driver_slug');
    }

    public function fleet()
    {
        return $this->belongsTo(Fleet::class, 'fleet_slug', 'fleet_slug');
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class, 'zone_slug', 'zone_slug');
    }
}
