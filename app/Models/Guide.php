<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Guide extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'guide_slug',
        'title',
        'category', // bin_use|bulky_waste|hazardous|pickup_procedure|scanning|onboarding|safety|compliance|other
        'content',
        'attachments',
        'audience', // client|provider|all
        'status',   // active|inactive
    ];

    protected $casts = [
        'attachments' => 'array',
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}

