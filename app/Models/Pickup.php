<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pickup extends Model
{
    protected $fillable = [
        'code',
        'client_id',
        'title',
        'category',
        'description',
        'status',
        'location',
        'images',
    ];

    protected $casts = [
        'images' => 'array',
    ];
}
