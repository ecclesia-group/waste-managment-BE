<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pickup extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
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
    ];

    protected $casts = [
        'images'     => 'array',
        'amount'     => 'float',
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function provider()
    {
        return $this->belongsTo(Provider::class, 'provider_slug', 'provider_slug');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_slug', 'client_slug');
    }
}
