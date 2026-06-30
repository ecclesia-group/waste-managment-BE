<?php
namespace App\Models;

use App\Traits\ScopesProviderOrganisation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pickup extends Model
{
    use ScopesProviderOrganisation, SoftDeletes;

    protected $fillable = [
        'code',
        'bulk_waste_request_code',
        'client_slug',
        'title',
        'category',
        'description',
        'status',
        'location',
        'images',
        'pickup_date',
        'amount',
        'provider_slug',
        "scan_status",
        'route_planner_id',
        'group_slug',
        'scanned_at',
        'unscanned_at',
    ];

    protected $casts = [
        'images'       => 'array',
        'amount'       => 'float',
        'pickup_date'  => 'datetime',
        'scanned_at'   => 'datetime',
        'unscanned_at' => 'datetime',
        'deleted_at'   => 'datetime',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    public function provider()
    {
        return $this->belongsTo(Provider::class, 'provider_slug', 'provider_slug');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_slug', 'client_slug');
    }

    public function getRouteKeyName(): string
    {
        return "code";
    }

    public function bulkWasteRequest()
    {
        return $this->belongsTo(BulkWasteRequest::class, 'bulk_waste_request_code', 'request_code');
    }

    public function routePlanner()
    {
        return $this->belongsTo(RoutePlanner::class, 'route_planner_id');
    }
}
