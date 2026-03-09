<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Banner extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'banner_slug',
        'title',
        'message',
        'image',
        'audience', // client|provider|all
        'status',   // active|inactive
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'image' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}

