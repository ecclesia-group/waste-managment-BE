<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    use HasFactory;

    protected $fillable = [
        'actor',
        'actor_id',
        'code',
        'location',
        'description',
        'status',
        'images',
        'videos',
    ];

    protected $casts = [
        'images' => 'array',
        'videos' => 'array',
    ];
}
