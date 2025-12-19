<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pickup extends Model
{
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
    ];

    protected $casts = [
        'images' => 'array',
        'amount' => 'float',
    ];
}
