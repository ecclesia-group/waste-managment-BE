<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BulkWasteRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'request_code',
        'client_slug',
        'provider_slug',
        'title',
        'category',
        'description',
        'location',
        'images',
        'pickup_date',
        'status',
        'approved_at',
        'rejected_at',
        'rejection_reason',
    ];

    protected $casts = [
        'images' => 'array',
        'requested_pickup_date' => 'datetime',
        'pickup_date' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_slug', 'client_slug');
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class, 'provider_slug', 'provider_slug');
    }

    public function getRouteKeyName(): string
    {
        return 'request_code';
    }
}
