<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WeighbridgeRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'facility_slug',
        'provider_slug',
        'fleet_slug',
        'fleet_code',
        'gross_weight',
        'amount',
        'zone_slug',
        'payment_status',
        'scan_status',
        'notes',
    ];

    protected $casts = [
        'amount'     => 'float',
        'gross_weight' => 'float',
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function facility()
    {
        return $this->belongsTo(Facility::class, 'facility_slug', 'facility_slug');
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class, 'provider_slug', 'provider_slug');
    }

    public function fleet()
    {
        return $this->belongsTo(Fleet::class, 'fleet_slug', 'fleet_slug');
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class, 'zone_slug', 'zone_slug');
    }

    public function getRouteKeyName(): string
    {
        return "code";
    }
}
