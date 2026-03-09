<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WasteHandoverRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'requester_provider_slug',
        'target_provider_slug',
        'title',
        'waste_types',
        'description',
        'pickup_location',
        'images',
        'fee_amount',
        'status', // pending|accepted|declined|completed|cancelled
        'accepted_at',
        'completed_at',
    ];

    protected $casts = [
        'waste_types' => 'array',
        'images' => 'array',
        'fee_amount' => 'float',
        'accepted_at' => 'datetime',
        'completed_at' => 'datetime',
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}

