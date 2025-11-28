<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'region',
        'description',
        'locations',
    ];

    protected $casts = [
        'locations' => 'array',
    ];
}
