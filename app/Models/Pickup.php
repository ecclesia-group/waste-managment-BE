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
    ];

    protected $casts = [
        'images'     => 'array',
        'amount'     => 'float',
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
