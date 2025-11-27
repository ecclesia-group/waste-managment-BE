<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    protected $fillable = [
        'code',
        'actor',
        'actor_id',
        'ratings',
        'comments',
        'status',
    ];
}
